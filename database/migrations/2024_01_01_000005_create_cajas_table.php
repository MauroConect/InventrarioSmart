<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cajas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users')->onDelete('restrict');
            $table->datetime('fecha_apertura');
            $table->datetime('fecha_cierre')->nullable();
            $table->decimal('monto_apertura', 10, 2)->default(0);
            $table->decimal('monto_cierre', 10, 2)->nullable();
            $table->decimal('monto_esperado', 10, 2)->nullable();
            $table->decimal('monto_real', 10, 2)->nullable();
            $table->decimal('diferencia', 10, 2)->nullable();
            $table->enum('estado', ['abierta', 'cerrada'])->default('abierta');
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cajas');
    }
};
