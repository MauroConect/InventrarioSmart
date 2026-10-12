<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('productos')) {
            return;
        }

        DB::statement('ALTER TABLE productos MODIFY stock_actual DECIMAL(12,3) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE productos MODIFY stock_minimo DECIMAL(12,3) NOT NULL DEFAULT 0');

        if (Schema::hasTable('movimientos_stock')) {
            DB::statement('ALTER TABLE movimientos_stock MODIFY cantidad DECIMAL(12,3) NOT NULL');
            DB::statement('ALTER TABLE movimientos_stock MODIFY cantidad_anterior DECIMAL(12,3) NOT NULL');
            DB::statement('ALTER TABLE movimientos_stock MODIFY cantidad_actual DECIMAL(12,3) NOT NULL');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('productos')) {
            return;
        }

        DB::statement('ALTER TABLE productos MODIFY stock_actual INT NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE productos MODIFY stock_minimo INT NOT NULL DEFAULT 0');

        if (Schema::hasTable('movimientos_stock')) {
            DB::statement('ALTER TABLE movimientos_stock MODIFY cantidad INT NOT NULL');
            DB::statement('ALTER TABLE movimientos_stock MODIFY cantidad_anterior INT NOT NULL');
            DB::statement('ALTER TABLE movimientos_stock MODIFY cantidad_actual INT NOT NULL');
        }
    }
};
