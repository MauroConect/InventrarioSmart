<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE ventas DROP CONSTRAINT IF EXISTS ventas_estado_check');
            DB::statement("ALTER TABLE ventas ADD CONSTRAINT ventas_estado_check CHECK (estado IN ('pendiente','completada','cancelada','abierta','cerrada'))");
            DB::statement("ALTER TABLE ventas ALTER COLUMN estado SET DEFAULT 'abierta'");
            return;
        }
        DB::statement("
            ALTER TABLE ventas
            MODIFY COLUMN estado ENUM('pendiente','completada','cancelada','abierta','cerrada')
            NOT NULL DEFAULT 'abierta'
        ");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE ventas DROP CONSTRAINT IF EXISTS ventas_estado_check');
            DB::statement("ALTER TABLE ventas ADD CONSTRAINT ventas_estado_check CHECK (estado IN ('pendiente','completada','cancelada'))");
            DB::statement("ALTER TABLE ventas ALTER COLUMN estado SET DEFAULT 'completada'");
            return;
        }
        DB::statement("
            ALTER TABLE ventas
            MODIFY COLUMN estado ENUM('pendiente','completada','cancelada')
            NOT NULL DEFAULT 'completada'
        ");
    }
};
