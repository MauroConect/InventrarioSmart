<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracion_comercio', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        if (DB::table('configuracion_comercio')->whereNull('tenant_id')->exists()) {
            $tenantId = DB::table('tenants')->where('slug', 'legacy-default')->value('id');

            if (! $tenantId) {
                $tenantId = DB::table('tenants')->insertGetId([
                    'uuid' => (string) Str::uuid(),
                    'slug' => 'legacy-default',
                    'name' => 'Comercio legado',
                    'rubro_profile' => 'base',
                    'status' => 'active',
                    'timezone' => 'UTC',
                    'activated_at' => now(),
                    'onboarding_completed_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('configuracion_comercio')->whereNull('tenant_id')->update(['tenant_id' => $tenantId]);
        }

        Schema::table('configuracion_comercio', function (Blueprint $table) {
            $table->unique('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::table('configuracion_comercio', function (Blueprint $table) {
            $table->dropUnique(['tenant_id']);
            $table->dropConstrainedForeignId('tenant_id');
        });
    }
};
