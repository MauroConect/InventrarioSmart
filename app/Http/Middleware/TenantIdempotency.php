<?php

namespace App\Http\Middleware;

use App\Support\Tenancy\{TenantContext, TenantRuntime};
use Closure;
use Illuminate\Http\{Request, UploadedFile};
use Illuminate\Support\Facades\DB;

class TenantIdempotency
{
    public function handle(Request $request, Closure $next)
    {
        $key = $request->header('Idempotency-Key');
        if (! $key || ! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true) || ! $request->is('api/ventas*', 'api/cajas*', 'api/movimientos-stock', 'api/movimientos-caja*') || $request->is('api/ventas/*/facturar-afip', 'api/ventas-facturar-lote')) { return $next($request); }
        abort_unless(preg_match('/^[a-zA-Z0-9_.:-]{8,128}$/', $key), 422, 'Clave de operación inválida.');
        $tenant = app(TenantContext::class)->id(); $actor = $request->user()->id;
        $fingerprint = hash_hmac('sha256', json_encode([$request->method(), $request->path(), $this->canonical($request->all())]), config('app.key'));
        return app(TenantRuntime::class)->lock('operation:'.$actor.':'.hash('sha256', $key), 120)->block(15, function () use ($request, $next, $tenant, $actor, $key, $fingerprint) {
            return DB::transaction(function () use ($request, $next, $tenant, $actor, $key, $fingerprint) {
                $query = DB::table('tenant_operations')->where('tenant_id', $tenant)->where('actor_user_id', $actor)->where('key', $key);
                if ($previous = $query->first()) {
                    abort_unless(hash_equals($previous->fingerprint, $fingerprint), 409, 'La clave ya corresponde a otra operación.');
                    return response($previous->response, $previous->status, ['Content-Type' => 'application/json', 'Idempotency-Replayed' => 'true']);
                }
                $response = $next($request);
                if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                    DB::table('tenant_operations')->insert(['tenant_id' => $tenant, 'actor_user_id' => $actor, 'key' => $key, 'fingerprint' => $fingerprint, 'status' => $response->getStatusCode(), 'response' => $response->getContent(), 'created_at' => now(), 'updated_at' => now()]);
                }
                return $response;
            });
        });
    }

    private function canonical(mixed $value): mixed
    {
        if ($value instanceof UploadedFile) { return ['sha256' => hash_file('sha256', $value->getRealPath()), 'name' => $value->getClientOriginalName()]; }
        if (! is_array($value)) { return $value; }
        if (! array_is_list($value)) { ksort($value); }
        return array_map(fn ($item) => $this->canonical($item), $value);
    }
}
