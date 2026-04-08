<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE ventas
            MODIFY COLUMN estado ENUM('pendiente','completada','cancelada','abierta','cerrada')
            NOT NULL DEFAULT 'abierta'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE ventas
            MODIFY COLUMN estado ENUM('pendiente','completada','cancelada')
            NOT NULL DEFAULT 'completada'
        ");
    }
};
