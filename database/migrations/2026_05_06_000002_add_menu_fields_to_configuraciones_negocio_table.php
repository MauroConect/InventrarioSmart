<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuraciones_negocio', function (Blueprint $table) {
            $table->string('moneda', 10)->default('ARS')->after('descripcion_corta');
            $table->string('telefono_whatsapp', 30)->nullable()->after('moneda');
            $table->string('instagram_url', 500)->nullable()->after('telefono_whatsapp');
            $table->string('direccion', 255)->nullable()->after('instagram_url');
            $table->string('horario_atencion', 255)->nullable()->after('direccion');
            $table->string('mensaje_bienvenida', 255)->nullable()->after('horario_atencion');
            $table->boolean('mostrar_precios')->default(true)->after('mensaje_bienvenida');
            $table->boolean('mostrar_descripciones')->default(true)->after('mostrar_precios');
        });
    }

    public function down(): void
    {
        Schema::table('configuraciones_negocio', function (Blueprint $table) {
            $table->dropColumn([
                'moneda',
                'telefono_whatsapp',
                'instagram_url',
                'direccion',
                'horario_atencion',
                'mensaje_bienvenida',
                'mostrar_precios',
                'mostrar_descripciones',
            ]);
        });
    }
};
