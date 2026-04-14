<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('ventas', 'estado_facturacion')) return;
        Schema::table('ventas', function (Blueprint $table) {
            $table->string('estado_facturacion', 20)->default('pendiente')->after('estado');
            $table->string('comprobante_tipo', 10)->nullable()->after('estado_facturacion');
            $table->unsignedBigInteger('comprobante_numero')->nullable()->after('comprobante_tipo');
            $table->string('cae', 20)->nullable()->after('comprobante_numero');
            $table->date('cae_vencimiento')->nullable()->after('cae');
            $table->text('afip_observaciones')->nullable()->after('cae_vencimiento');
            $table->timestamp('facturada_at')->nullable()->after('afip_observaciones');
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn([
                'estado_facturacion',
                'comprobante_tipo',
                'comprobante_numero',
                'cae',
                'cae_vencimiento',
                'afip_observaciones',
                'facturada_at',
            ]);
        });
    }
};
