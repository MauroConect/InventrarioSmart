<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuraciones_negocio', function (Blueprint $table) {
            $table->string('color_fondo', 20)->default('#fff7ed')->after('color_primario');
            $table->string('color_texto', 20)->default('#111827')->after('color_fondo');
            $table->string('color_tarjeta', 20)->default('#ffffff')->after('color_texto');
            $table->string('color_header', 20)->default('#ffffff')->after('color_tarjeta');
            $table->string('color_chip', 20)->default('#ea580c')->after('color_header');
        });
    }

    public function down(): void
    {
        Schema::table('configuraciones_negocio', function (Blueprint $table) {
            $table->dropColumn([
                'color_fondo',
                'color_texto',
                'color_tarjeta',
                'color_header',
                'color_chip',
            ]);
        });
    }
};
