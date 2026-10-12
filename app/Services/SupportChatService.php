<?php

namespace App\Services;

use App\Models\SupportConversation;
use App\Models\SupportMessage;
use App\Models\SupportRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SupportChatService
{
    public function __construct(
        private readonly SupportRequestClassifier $classifier,
        private readonly SupportLLMService $llm,
    ) {}

    public function start(User $user, string $body, ?string $subject = null, array $context = []): SupportConversation
    {
        $conversation = SupportConversation::query()->create([
            'user_id' => $user->getKey(),
            'subject' => $subject ?: Str::limit(trim($body), 100, ''),
            'status' => SupportConversation::STATUS_OPEN,
            'last_message_at' => now(),
        ]);

        $this->postUserMessage($conversation, $user, $body, $context);

        return $conversation->fresh(['messages', 'supportRequest']);
    }

    public function postUserMessage(
        SupportConversation $conversation,
        User $user,
        string $body,
        array $context = [],
    ): SupportMessage {
        $classification = $this->classifier->classify($body);

        $userMessage = DB::transaction(function () use ($conversation, $user, $body, $classification, $context) {
            $message = $conversation->messages()->create([
                'sender_id' => $user->getKey(),
                'role' => SupportMessage::ROLE_USER,
                'body' => trim($body),
                'metadata' => ['classification' => $classification, 'context' => $context],
            ]);

            SupportRequest::query()->firstOrCreate(
                ['support_conversation_id' => $conversation->getKey()],
                [
                    'support_message_id' => $message->getKey(),
                    'type' => $classification['type'],
                    'status' => SupportRequest::STATUS_OPEN,
                    'priority' => $classification['priority'],
                    'title' => $conversation->subject,
                    'description' => trim($body),
                    'metadata' => ['classification_confidence' => $classification['confidence']],
                ]
            );

            $conversation->update([
                'status' => SupportConversation::STATUS_WAITING_PLATFORM,
                'last_message_at' => now(),
                'closed_at' => null,
            ]);

            return $message;
        });

        $history = $conversation->messages()->get();
        $reply = $this->llm->reply($history, $classification);

        $conversation->messages()->create([
            'role' => SupportMessage::ROLE_ASSISTANT,
            'body' => $reply['body'],
            'metadata' => [
                'provider' => $reply['provider'],
                'fallback' => $reply['fallback'],
            ],
        ]);

        $conversation->update(['last_message_at' => now()]);

        return $userMessage;
    }
}
