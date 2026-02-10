<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caja_id')->constrained('cajas')->onDelete('restrict');
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->onDelete('set null');
            $table->string('numero_factura')->unique();
            $table->datetime('fecha');
            $table->decimal('total', 10, 2)->default(0);
            $table->decimal('descuento', 10, 2)->default(0);
            $table->decimal('total_final', 10, 2)->default(0);
            $table->enum('tipo_pago', ['efectivo', 'tarjeta', 'cuenta_corriente', 'mixto']);
            $table->enum('estado', ['pendiente', 'completada', 'cancelada'])->default('completada');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
