<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Crypt, DB, Schema};

return new class extends Migration
{
    public function up(): void
    {
        $rows = DB::table('configuraciones_fiscales')->get();
        $tenants = DB::table('tenants')->pluck('id');
        if ($rows->isNotEmpty() && ($tenants->count() !== 1 || $rows->count() !== 1)) {
            throw new RuntimeException('La configuración fiscal global requiere reconciliar un dueño antes de migrar.');
        }
        Schema::table('configuraciones_fiscales', function (Blueprint $t) {
            $t->unsignedBigInteger('tenant_id')->nullable();
            $t->text('passphrase_certificado')->nullable()->change();
        });
        foreach ($rows as $row) {
            DB::table('configuraciones_fiscales')->where('id', $row->id)->update(['tenant_id' => $tenants->first(), 'passphrase_certificado' => $row->passphrase_certificado ? Crypt::encryptString($row->passphrase_certificado) : null]);
        }
        Schema::table('configuraciones_fiscales', function (Blueprint $t) {
            $t->unsignedBigInteger('tenant_id')->nullable(false)->change();
            $t->foreign('tenant_id')->references('id')->on('tenants')->restrictOnDelete();
            $t->unique('tenant_id');
            $t->unique(['cuit_emisor', 'punto_venta', 'ambiente'], 'fiscal_issuer_point_environment_unique');
        });
    }

    public function down(): void
    {
        // Never decrypt secrets or silently remove ownership from populated fiscal configurations.
        if (DB::table('configuraciones_fiscales')->exists()) { throw new RuntimeException('Rollback fiscal requiere reconciliación explícita; no descifra secretos.'); }
        Schema::table('configuraciones_fiscales', function (Blueprint $t) {
            $t->dropForeign(['tenant_id']); $t->dropUnique(['tenant_id']);
            $t->dropUnique('fiscal_issuer_point_environment_unique'); $t->dropColumn('tenant_id');
        });
    }
};
