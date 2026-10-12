<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('support_conversation_id')
                ->constrained('support_conversations')
                ->cascadeOnDelete();
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('role', 24);
            $table->text('body');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(
                ['tenant_id', 'support_conversation_id', 'id'],
                'support_messages_tenant_conversation_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_messages');
    }
};
