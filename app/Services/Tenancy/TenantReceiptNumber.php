<?php

namespace App\Services\Tenancy;

use App\Models\Venta;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;

class TenantReceiptNumber
{
    public function next(string $series): string
    {
        if (! in_array($series, ['FAC', 'CMD'], true)) { throw new \InvalidArgumentException('Serie interna inválida.'); }
        $tenant = app(TenantContext::class)->id();
        return DB::transaction(function () use ($series, $tenant) {
            $numberType = DB::getDriverName() === 'pgsql' ? 'BIGINT' : 'UNSIGNED';
            $legacy = (int) Venta::where('numero_factura', 'like', $series.'-%')->selectRaw("COALESCE(MAX(CAST(SUBSTRING(numero_factura, 5) AS {$numberType})), 0) AS last_number")->value('last_number');
            DB::table('tenant_receipt_counters')->insertOrIgnore(['tenant_id' => $tenant, 'series' => $series, 'value' => $legacy]);
            $row = DB::table('tenant_receipt_counters')->where('tenant_id', $tenant)->where('series', $series)->lockForUpdate()->first();
            $value = $row->value + 1;
            DB::table('tenant_receipt_counters')->where('id', $row->id)->update(['value' => $value]);
            return $series.'-'.str_pad((string) $value, 8, '0', STR_PAD_LEFT);
        }, 3);
    }
}
