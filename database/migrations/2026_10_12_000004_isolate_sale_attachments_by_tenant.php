<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $invalid = DB::table('venta_adjuntos as a')->leftJoin('ventas as v', 'v.id', '=', 'a.venta_id')->whereNull('v.tenant_id')->exists();
        if ($invalid) { throw new RuntimeException('Hay adjuntos sin una venta con comercio; reconciliar antes de migrar.'); }
        Schema::table('venta_adjuntos', function (Blueprint $t) { $t->unsignedBigInteger('tenant_id')->nullable(); });
        DB::statement('UPDATE venta_adjuntos a INNER JOIN ventas v ON v.id = a.venta_id SET a.tenant_id = v.tenant_id');
        Schema::table('venta_adjuntos', function (Blueprint $t) {
            $t->unsignedBigInteger('tenant_id')->nullable(false)->change();
            $t->foreign('tenant_id')->references('id')->on('tenants')->restrictOnDelete();
            $t->index(['tenant_id', 'venta_id']);
        });
    }

    public function down(): void
    {
        Schema::table('venta_adjuntos', function (Blueprint $t) { $t->dropForeign(['tenant_id']); $t->dropIndex(['tenant_id', 'venta_id']); $t->dropColumn('tenant_id'); });
    }
};
