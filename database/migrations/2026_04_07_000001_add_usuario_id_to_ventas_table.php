<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('ventas', 'usuario_id')) {
            Schema::table('ventas', function (Blueprint $table) {
                $table->foreignId('usuario_id')
                    ->nullable()
                    ->after('cliente_id')
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement(
                'UPDATE ventas v INNER JOIN cajas c ON v.caja_id = c.id SET v.usuario_id = c.usuario_id WHERE v.usuario_id IS NULL AND c.usuario_id IS NOT NULL'
            );
        } elseif ($driver === 'sqlite') {
            DB::statement(
                'UPDATE ventas SET usuario_id = (SELECT c.usuario_id FROM cajas c WHERE c.id = ventas.caja_id) WHERE usuario_id IS NULL AND EXISTS (SELECT 1 FROM cajas c2 WHERE c2.id = ventas.caja_id AND c2.usuario_id IS NOT NULL)'
            );
        }
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropForeign(['usuario_id']);
            $table->dropColumn('usuario_id');
        });
    }
};
