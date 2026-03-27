<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuraciones_fiscales', function (Blueprint $table) {
            $table->id();
            $table->string('razon_social')->nullable();
            $table->string('cuit_emisor', 20)->nullable();
            $table->string('condicion_iva', 30)->default('monotributo');
            $table->unsignedInteger('punto_venta')->nullable();
            $table->string('ambiente', 20)->default('homologacion');
            $table->string('comprobante_tipo_default', 5)->default('B');
            $table->string('certificado_path')->nullable();
            $table->string('clave_privada_path')->nullable();
            $table->string('passphrase_certificado')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuraciones_fiscales');
    }
};
