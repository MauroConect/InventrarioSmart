<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('support_conversation_id')
                ->unique()
                ->constrained('support_conversations')
                ->cascadeOnDelete();
            $table->foreignId('support_message_id')
                ->nullable()
                ->constrained('support_messages')
                ->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 32)->index();
            $table->string('status', 32)->default('open')->index();
            $table->string('priority', 16)->default('normal')->index();
            $table->string('title');
            $table->text('description');
            $table->json('metadata')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status', 'priority'], 'support_requests_tenant_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_requests');
    }
};
