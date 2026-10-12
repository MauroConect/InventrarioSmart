<?php

namespace App\Http\Controllers;

use App\Models\SupportConversation;
use App\Models\SupportRequest;
use App\Models\User;
use App\Services\SupportChatService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportChatController extends Controller
{
    public function __construct(
        private readonly SupportChatService $chat,
        private readonly TenantContext $tenancy,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        return response()->json(
            SupportConversation::query()
                ->where('user_id', $user->getKey())
                ->withCount(['messages as unread_count' => fn ($query) => $query
                    ->where('role', \App\Models\SupportMessage::ROLE_PLATFORM)
                    ->whereRaw('support_messages.id > support_conversations.user_last_read_message_id')])
                ->with('supportRequest')
                ->orderByDesc('last_message_at')
                ->paginate(20)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $validated = $request->validate([
            'message' => ['required', 'string', 'min:2', 'max:4000'],
            'subject' => ['nullable', 'string', 'max:255'],
            'screen' => ['nullable', 'string', 'max:120'],
        ]);

        $conversation = $this->chat->start(
            $user,
            $validated['message'],
            $validated['subject'] ?? null,
            $this->supportContext($user, $validated['screen'] ?? null),
        );

        return response()->json($conversation, 201);
    }

    public function show(Request $request, int $conversation): JsonResponse
    {
        $model = $this->ownedConversation($request, $conversation);

        $model->update(['user_last_read_message_id' => (int) $model->messages()->max('id')]);

        return response()->json($model->load(['messages.sender:id,name', 'supportRequest']));
    }

    public function storeMessage(Request $request, int $conversation): JsonResponse
    {
        $model = $this->ownedConversation($request, $conversation);
        abort_if($model->status === SupportConversation::STATUS_CLOSED, 409, 'La conversación está cerrada.');

        $validated = $request->validate([
            'message' => ['required', 'string', 'min:2', 'max:4000'],
            'screen' => ['nullable', 'string', 'max:120'],
        ]);

        $this->chat->postUserMessage(
            $model,
            $request->user(),
            $validated['message'],
            $this->supportContext($request->user(), $validated['screen'] ?? null)
        );

        return response()->json(
            $model->fresh(['messages', 'supportRequest']),
            201
        );
    }

    public function poll(Request $request, int $conversation): JsonResponse
    {
        $model = $this->ownedConversation($request, $conversation);
        $validated = $request->validate([
            'after_id' => ['nullable', 'integer', 'min:0'],
            'mark_read' => ['nullable', 'boolean'],
        ]);

        $messages = $model->messages()
            ->where('id', '>', (int) ($validated['after_id'] ?? 0))
            ->with('sender:id,name')
            ->limit(100)
            ->get();
        if ($messages->isNotEmpty() && ($validated['mark_read'] ?? false)) {
            $model->update(['user_last_read_message_id' => $messages->last()->getKey()]);
        }

        return response()->json([
            'conversation_status' => $model->status,
            'messages' => $messages,
            'last_id' => (int) ($messages->last()?->getKey() ?? ($validated['after_id'] ?? 0)),
        ]);
    }

    public function close(Request $request, int $conversation): JsonResponse
    {
        $model = $this->ownedConversation($request, $conversation);

        $model->update([
            'status' => SupportConversation::STATUS_CLOSED,
            'closed_at' => now(),
        ]);
        $model->supportRequest()->update([
            'status' => SupportRequest::STATUS_CLOSED,
            'resolved_at' => now(),
        ]);

        return response()->json($model->fresh('supportRequest'));
    }

    private function ownedConversation(Request $request, int $id): SupportConversation
    {
        $user = $this->authenticatedUser($request);

        return SupportConversation::query()
            ->where('user_id', $user->getKey())
            ->findOrFail($id);
    }

    private function authenticatedUser(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401, 'Autenticación requerida.');

        return $user;
    }

    private function supportContext(User $user, ?string $screen): array
    {
        $modules = [];
        if ($this->tenancy->hasTenant()) {
            try {
                $modules = $this->tenancy->tenant()
                    ->moduleGrants()
                    ->where('enabled', true)
                    ->pluck('module_key')
                    ->all();
            } catch (\Throwable) {
                $modules = (array) $this->tenancy->tenant()->features;
            }
        }

        return [
            'screen' => $screen,
            'role' => $user->currentTenantRole(),
            'modules' => $modules,
        ];
    }
}
