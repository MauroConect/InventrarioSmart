<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuraciones_negocio', function (Blueprint $table) {
            $table->string('color_precio', 20)->default('#ea580c')->after('color_chip');
            $table->string('color_categoria', 20)->default('#f97316')->after('color_precio');
        });
    }

    public function down(): void
    {
        Schema::table('configuraciones_negocio', function (Blueprint $table) {
            $table->dropColumn([
                'color_precio',
                'color_categoria',
            ]);
        });
    }
};
