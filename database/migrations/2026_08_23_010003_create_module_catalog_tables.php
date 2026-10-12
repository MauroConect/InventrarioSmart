<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_modules', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('depends_on')->nullable();
            $table->boolean('default_enabled')->default(false);
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_ready')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('tenant_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('module_key');
            $table->boolean('enabled')->default(true);
            $table->json('config')->nullable();
            $table->timestamp('enabled_at')->nullable();
            $table->foreignId('enabled_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->foreign('module_key')->references('key')->on('platform_modules')->cascadeOnDelete();
            $table->unique(['tenant_id', 'module_key']);
            $table->index(['tenant_id', 'enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_modules');
        Schema::dropIfExists('platform_modules');
    }
};
