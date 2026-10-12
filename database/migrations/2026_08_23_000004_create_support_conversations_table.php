<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_conversations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('subject');
            $table->string('status', 32)->default('open')->index();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->unsignedBigInteger('user_last_read_message_id')->default(0);
            $table->unsignedBigInteger('platform_last_read_message_id')->default(0);
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'user_id', 'last_message_at'], 'support_conversations_tenant_user_last_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_conversations');
    }
};
