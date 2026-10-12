<?php

namespace Tests\Feature;

use App\Models\{Caja, Categoria, Cliente, CuentaCorriente, MovimientoCuentaCorriente, Producto, TenantModule, User, Venta};
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\{InputStream, Process};
use Tests\MySqlTenantTestCase;

class SaleConcurrencyTest extends MySqlTenantTestCase
{
    private function concurrent(array $specs): array
    {
        $processes = []; $inputs = [];
        $cashId = $specs[0]['payload']['caja_id'] ?? null;
        if (! $cashId && preg_match('/^\/api\/ventas\/(\d+)\/cerrar$/', $specs[0]['path'], $match)) { $cashId = DB::table('ventas')->where('id', $match[1])->value('caja_id'); }
        DB::beginTransaction();
        DB::table('cajas')->where('id', $cashId)->lockForUpdate()->first();
        foreach ($specs as $spec) {
            $input = new InputStream;
            $input->write(json_encode($spec)."\n");
            $process = new Process([PHP_BINARY, base_path('tests/Support/concurrent-request.php')], base_path(), ['APP_ENV' => 'testing', 'DB_DATABASE' => 'testing', 'DB_HOST' => config('database.connections.mysql.host'), 'DB_PORT' => config('database.connections.mysql.port'), 'DB_USERNAME' => config('database.connections.mysql.username'), 'DB_PASSWORD' => config('database.connections.mysql.password'), 'CACHE_DRIVER' => 'file', 'SESSION_DRIVER' => 'array', 'MAIL_MAILER' => 'array', 'QUEUE_CONNECTION' => 'sync']);
            $process->setInput($input); $process->setTimeout(30); $process->start();
            $processes[] = $process; $inputs[] = $input;
        }
        try {
            $deadline = microtime(true) + 20;
            while (array_filter($processes, fn ($p) => ! str_contains($p->getOutput(), "READY\n"))) {
                foreach ($processes as $process) { if (! $process->isRunning() && ! str_contains($process->getOutput(), "READY\n")) { $this->fail('Concurrency worker stopped: '.$process->getOutput().' '.$process->getErrorOutput()); } }
                if (microtime(true) > $deadline) { $this->fail('Concurrency workers did not become ready: '.implode(' ', array_map(fn ($p) => $p->getOutput().' '.$p->getErrorOutput(), $processes))); }
                usleep(20000);
            }
            foreach ($inputs as $input) { $input->write("GO\n"); $input->close(); }
            $deadline = microtime(true) + 10;
            while (array_filter($processes, fn ($p) => ! str_contains($p->getOutput(), "STARTED\n"))) {
                if (microtime(true) > $deadline) { $this->fail('Workers did not start concurrently.'); }
                usleep(10000);
            }
            DB::commit();
            $results = [];
            foreach ($processes as $process) { $process->wait(); $this->assertTrue($process->isSuccessful(), $process->getErrorOutput()); $results[] = json_decode(trim(explode("STARTED\n", $process->getOutput(), 2)[1]), true, flags: JSON_THROW_ON_ERROR); }
            $this->assertGreaterThan(max(array_column($results, 'started')), min(array_column($results, 'finished')), 'Requests must overlap in wall time.');
            return $results;
        } finally { while (DB::transactionLevel() > 0) { DB::rollBack(); } foreach ($processes as $process) { if ($process->isRunning()) { $process->stop(); } } }
    }

    public function test_parallel_retries_and_competing_stock_sales_preserve_invariants(): void
    {
        [$tenant, $operator] = $this->commerce();
        $second = User::create(['name' => 'Segundo ficticio', 'email' => 'second@example.test', 'password' => 'Ficticio-12345!']);
        $second->forceFill(['email_verified_at' => now()])->save();
        $tenant->users()->attach($second, ['role' => 'admin', 'status' => 'active']);
        foreach (['sales.open_orders', 'finance.credit'] as $module) { TenantModule::updateOrCreate(['tenant_id' => $tenant->id, 'module_key' => $module], ['enabled' => true]); }
        [$cash, $product, $client, $account] = app(TenantContext::class)->run($tenant, function () use ($operator) {
            $category = Categoria::create(['nombre' => 'Concurrente']);
            $product = Producto::create(['codigo' => 'CONCURRENT', 'nombre' => 'Ficticio', 'categoria_id' => $category->id, 'precio_venta' => 10, 'stock_actual' => 4]);
            $cash = Caja::create(['usuario_id' => $operator->id, 'fecha_apertura' => now(), 'monto_apertura' => 0]);
            $client = Cliente::create(['nombre' => 'Ficticio', 'apellido' => 'Concurrente', 'dni' => 'CONC-ONLY']);
            $account = CuentaCorriente::create(['cliente_id' => $client->id, 'saldo' => 0, 'limite_credito' => 1000, 'activa' => true]);
            return [$cash, $product, $client, $account];
        });
        // Child connections need committed fixtures; all tables are dropped after this class.
        DB::commit();
        $payload = ['caja_id' => $cash->id, 'tipo_pago' => 'efectivo', 'items' => [['producto_id' => $product->id, 'cantidad' => 1]]];
        $spec = ['actor' => $operator->id, 'tenant' => $tenant->id, 'path' => '/api/ventas', 'payload' => $payload, 'key' => 'concurrent-retry-'.str()->random(12)];
        $results = $this->concurrent([$spec, $spec]);
        $this->assertSame([201,201], array_column($results, 'status'));
        $this->assertSame($results[0]['body']['id'], $results[1]['body']['id']);
        app(TenantContext::class)->run($tenant, function () use ($product) { $this->assertSame(1, Venta::count()); $this->assertSame('3.000', $product->fresh()->stock_actual); });
        $competing = array_replace($spec, ['payload' => array_replace($payload, ['items' => [['producto_id' => $product->id, 'cantidad' => 3]]])]);
        $other = array_replace($competing, ['actor' => $second->id, 'key' => 'competing-second-'.str()->random(12)]);
        $competing['key'] = 'competing-first-'.str()->random(12);
        $results = $this->concurrent([$competing, $other]);
        $statuses = array_column($results, 'status'); sort($statuses);
        $this->assertSame([201,422], $statuses);
        app(TenantContext::class)->run($tenant, function () use ($product) { $this->assertSame(2, Venta::count()); $this->assertSame('0.000', $product->fresh()->stock_actual); });
        app(TenantContext::class)->run($tenant, fn () => $product->update(['stock_actual' => 2]));
        $this->signIn($tenant, $operator);
        $order = $this->postJson('/api/ventas/abrir', ['caja_id' => $cash->id])->assertCreated()->json('id');
        $this->postJson('/api/ventas/'.$order.'/items', ['items' => [['producto_id' => $product->id, 'cantidad' => 1]]])->assertOk();
        $close = ['actor' => $operator->id, 'tenant' => $tenant->id, 'path' => '/api/ventas/'.$order.'/cerrar', 'payload' => ['tipo_pago' => 'cuenta_corriente', 'cliente_id' => $client->id]];
        $results = $this->concurrent([$close, array_replace($close, ['actor' => $second->id])]);
        $this->assertSame([200,200], array_column($results, 'status'));
        app(TenantContext::class)->run($tenant, function () use ($account) { $this->assertSame('10.00', $account->fresh()->saldo); $this->assertSame(1, MovimientoCuentaCorriente::count()); });
    }
}
