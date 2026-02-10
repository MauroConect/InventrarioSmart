<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->decimal('monto_tarjeta', 10, 2)->nullable()->after('tipo_pago');
            $table->decimal('monto_efectivo', 10, 2)->nullable()->after('monto_tarjeta');
            $table->integer('cuotas')->nullable()->after('monto_efectivo');
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn(['monto_tarjeta', 'monto_efectivo', 'cuotas']);
        });
    }
};
