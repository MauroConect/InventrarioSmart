<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('deudas_clientes', 'cuotas_originales')) return;
        Schema::table('deudas_clientes', function (Blueprint $table) {
            $table->integer('cuotas_originales')->nullable()->after('monto_pendiente');
            $table->integer('cuotas_pagadas')->default(0)->after('cuotas_originales');
            $table->integer('cuotas_restantes')->nullable()->after('cuotas_pagadas');
        });
    }

    public function down(): void
    {
        Schema::table('deudas_clientes', function (Blueprint $table) {
            $table->dropColumn(['cuotas_originales', 'cuotas_pagadas', 'cuotas_restantes']);
        });
    }
};
