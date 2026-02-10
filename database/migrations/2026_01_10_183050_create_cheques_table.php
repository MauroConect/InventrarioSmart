<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cheques', function (Blueprint $table) {
            $table->id();
            $table->string('numero_cheque')->unique();
            $table->string('banco');
            $table->date('fecha_ingreso');
            $table->date('fecha_vencimiento');
            $table->decimal('monto', 10, 2);
            $table->enum('tipo', ['recibido', 'emitido'])->default('recibido');
            $table->enum('estado', ['pendiente', 'cobrado', 'vencido', 'rechazado', 'depositado'])->default('pendiente');
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->onDelete('set null');
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedores')->onDelete('set null');
            $table->foreignId('caja_id')->nullable()->constrained('cajas')->onDelete('set null');
            $table->date('fecha_cobro')->nullable();
            $table->text('observaciones')->nullable();
            $table->foreignId('usuario_id')->constrained('users')->onDelete('restrict');
            $table->timestamps();
            
            $table->index('fecha_vencimiento');
            $table->index('estado');
            $table->index('fecha_ingreso');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cheques');
    }
};
