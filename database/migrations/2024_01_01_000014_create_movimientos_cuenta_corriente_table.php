<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_cuenta_corriente', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuenta_corriente_id')->constrained('cuentas_corrientes')->onDelete('cascade');
            $table->enum('tipo', ['debe', 'haber']);
            $table->decimal('monto', 10, 2);
            $table->string('concepto');
            $table->text('observaciones')->nullable();
            $table->foreignId('venta_id')->nullable()->constrained('ventas')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_cuenta_corriente');
    }
};
