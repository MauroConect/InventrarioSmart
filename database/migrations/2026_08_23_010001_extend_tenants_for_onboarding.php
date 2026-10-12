<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->foreignId('owner_user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->timestamp('activated_at')->nullable()->after('status');
            $table->timestamp('suspended_at')->nullable()->after('activated_at');
            $table->text('suspension_reason')->nullable()->after('suspended_at');
            $table->timestamp('onboarding_completed_at')->nullable()->after('suspension_reason');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('owner_user_id');
            $table->dropColumn([
                'activated_at',
                'suspended_at',
                'suspension_reason',
                'onboarding_completed_at',
            ]);
        });
    }
};
