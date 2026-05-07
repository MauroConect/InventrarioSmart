<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuraciones_negocio', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_negocio', 255)->default('Danielles Bar & Buffet');
            $table->string('slogan', 255)->nullable();
            $table->string('logo_url', 500)->nullable();
            $table->string('color_primario', 20)->default('#ea580c');
            $table->text('descripcion_corta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuraciones_negocio');
    }
};
