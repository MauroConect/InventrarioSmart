<?php

namespace Database\Seeders;

use App\Models\Caja;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Tenant;
use App\Models\Turno;
use App\Models\User;
use App\Services\Modules\TenantFeatureService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Seeder;

class HospitalityAndAppointmentsDemoSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::query()->where('slug', 'legacy-default')->first();
        $admin = User::query()->where('email', 'admin@inventario.com')->first();

        if (! $tenant || ! $admin) {
            $this->command?->warn('Falta tenant legacy-default o admin@inventario.com.');

            return;
        }

        app(TenantContext::class)->set($tenant);

        app(TenantFeatureService::class)->sync($tenant, [
            'core.catalog',
            'core.stock',
            'core.pos',
            'core.cash',
            'catalog.scanner',
            'catalog.weighted',
            'sales.open_orders',
            'services.appointments',
            'platform.support',
        ], $admin->id);

        $categoria = Categoria::query()->firstOrCreate(
            ['nombre' => 'Mostrador'],
            ['descripcion' => 'Productos de demostración', 'activa' => true]
        );

        foreach ([
            ['codigo' => 'HELADO-K', 'nombre' => '1 kg helado', 'precio_venta' => 8500, 'tipo_venta' => 'peso', 'stock_actual' => 50],
            ['codigo' => 'CUCURUCHO', 'nombre' => 'Cucurucho', 'precio_venta' => 2500, 'tipo_venta' => 'unidad', 'stock_actual' => 80],
            ['codigo' => 'COCA-500', 'nombre' => 'Coca 500ml', 'precio_venta' => 1800, 'tipo_venta' => 'unidad', 'stock_actual' => 40],
        ] as $row) {
            Producto::query()->updateOrCreate(
                ['codigo' => $row['codigo']],
                $row + [
                    'precio_compra' => $row['precio_venta'] * 0.5,
                    'categoria_id' => $categoria->id,
                    'activo' => true,
                    'stock_minimo' => 5,
                ]
            );
        }

        if (! Caja::query()->where('estado', 'abierta')->exists()) {
            Caja::query()->create([
                'usuario_id' => $admin->id,
                'nombre' => 'Caja demostración',
                'fecha_apertura' => now(),
                'monto_apertura' => 10000,
                'estado' => 'abierta',
            ]);
        }

        $start = now()->startOfHour()->addHour();
        if (! Turno::query()->whereDate('starts_at', $start->toDateString())->exists()) {
            Turno::query()->create([
                'user_id' => $admin->id,
                'nombre' => 'Ana Pérez',
                'telefono' => '3415550000',
                'prestacion' => 'Control',
                'starts_at' => $start,
                'ends_at' => $start->copy()->addMinutes(30),
                'status' => 'scheduled',
            ]);
        }
    }
}
