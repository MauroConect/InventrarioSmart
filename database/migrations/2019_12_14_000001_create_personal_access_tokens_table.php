<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Esta migración no hace nada porque la tabla personal_access_tokens
     * ya fue creada por la migración 2014_10_12_200000_create_personal_access_tokens_table
     * que se ejecutó previamente.
     */
    public function up(): void
    {
        // La tabla ya existe, esta migración es solo para mantener compatibilidad
        // con Laravel Sanctum que espera esta migración
        if (!Schema::hasTable('personal_access_tokens')) {
            Schema::create('personal_access_tokens', function (Blueprint $table) {
                $table->id();
                $table->morphs('tokenable');
                $table->string('name');
                $table->string('token', 64)->unique();
                $table->text('abilities')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No eliminar la tabla porque fue creada por otra migración
        // Schema::dropIfExists('personal_access_tokens');
    }
};
