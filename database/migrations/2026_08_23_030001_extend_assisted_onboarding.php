<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_onboardings', function (Blueprint $table): void {
            $table->string('status')->default('registered')->after('step')->index();
            $table->timestamp('submitted_at')->nullable()->after('draft');
            $table->foreignId('assigned_to_user_id')->nullable()->after('tenant_id')->constrained('users')->nullOnDelete();
            $table->string('next_action')->nullable()->after('submitted_at');
            $table->timestamp('next_action_at')->nullable()->after('next_action');
            $table->timestamp('discovery_scheduled_at')->nullable()->after('next_action_at');
            $table->text('last_status_comment')->nullable()->after('discovery_scheduled_at');
            $table->string('suggested_profile')->nullable()->after('last_status_comment');
            $table->json('suggested_modules')->nullable()->after('suggested_profile');
            $table->text('customer_message')->nullable()->after('suggested_modules');
        });

        Schema::table('tenants', function (Blueprint $table): void {
            $table->string('repository_url')->nullable()->after('timezone');
            $table->string('core_version')->nullable()->after('repository_url');
            $table->string('environment_url')->nullable()->after('core_version');
            $table->text('delivery_notes')->nullable()->after('environment_url');
        });

        Schema::create('onboarding_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_onboarding_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->boolean('is_internal')->default(true);
            $table->timestamps();
        });

        Schema::create('onboarding_status_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_onboarding_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->text('comment')->nullable();
            $table->string('next_action')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_status_events');
        Schema::dropIfExists('onboarding_notes');

        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropColumn(['repository_url', 'core_version', 'environment_url', 'delivery_notes']);
        });

        Schema::table('tenant_onboardings', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('assigned_to_user_id');
            $table->dropColumn([
                'status',
                'submitted_at',
                'next_action',
                'next_action_at',
                'discovery_scheduled_at',
                'last_status_comment',
                'suggested_profile',
                'suggested_modules',
                'customer_message',
            ]);
        });
    }
};
