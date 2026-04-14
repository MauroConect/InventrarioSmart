<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('ventas', 'monto_transferencia')) {
            Schema::table('ventas', function (Blueprint $table) {
                $table->decimal('monto_transferencia', 10, 2)->nullable()->after('monto_efectivo');
            });
        }

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE ventas MODIFY tipo_pago VARCHAR(32) NOT NULL");
        }
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn('monto_transferencia');
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE ventas MODIFY tipo_pago ENUM('efectivo','tarjeta','cuenta_corriente','mixto') NOT NULL");
        }
    }
};
