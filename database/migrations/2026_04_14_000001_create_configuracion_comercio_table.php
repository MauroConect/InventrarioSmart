<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuracion_comercio', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_comercio')->default('Mi Comercio');
            $table->string('slogan')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('direccion')->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('sitio_web')->nullable();
            $table->string('color_primario', 7)->default('#1e40af');
            $table->string('color_sidebar', 7)->default('#1f2937');
            $table->text('mensaje_ticket')->nullable();
            $table->text('mensaje_footer')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracion_comercio');
    }
};
