<?php

namespace App\Http\Controllers;

use App\Models\SupportConversation;
use App\Models\SupportMessage;
use App\Models\SupportRequest;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlatformSupportController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenancy,
    ) {}

    public function inbox(Request $request): View
    {
        $this->platformUser($request);
        $filters = $request->validate([
            'status' => ['nullable', Rule::in([
                SupportRequest::STATUS_OPEN,
                SupportRequest::STATUS_IN_PROGRESS,
                SupportRequest::STATUS_RESOLVED,
                SupportRequest::STATUS_CLOSED,
            ])],
            'type' => ['nullable', Rule::in(SupportRequest::TYPES)],
        ]);

        $requests = $this->tenancy->runWithoutScope(function () use ($filters) {
            return SupportRequest::query()
                ->with(['conversation.tenant', 'conversation.user:id,name,email', 'assignee:id,name'])
                ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
                ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
                ->orderByRaw("case when priority = 'high' then 0 else 1 end")
                ->orderByDesc('updated_at')
                ->paginate(30)
                ->withQueryString();
        });

        return view('platform.support.index', compact('requests', 'filters'));
    }

    public function show(Request $request, int $conversation): View
    {
        $this->platformUser($request);
        $model = $this->findConversation($conversation);

        return view('platform.support.show', ['conversation' => $model]);
    }

    public function poll(Request $request, int $conversation): JsonResponse
    {
        $this->platformUser($request);
        $validated = $request->validate([
            'after_id' => ['nullable', 'integer', 'min:0'],
        ]);

        $messages = $this->tenancy->runWithoutScope(
            fn () => SupportMessage::query()
                ->where('support_conversation_id', $conversation)
                ->where('id', '>', (int) ($validated['after_id'] ?? 0))
                ->with('sender:id,name')
                ->orderBy('id')
                ->limit(100)
                ->get()
        );

        return response()->json([
            'messages' => $messages,
            'last_id' => (int) ($messages->last()?->getKey() ?? ($validated['after_id'] ?? 0)),
        ]);
    }

    public function reply(Request $request, int $conversation): JsonResponse
    {
        $operator = $this->platformUser($request);
        $validated = $request->validate([
            'message' => ['required', 'string', 'min:2', 'max:4000'],
        ]);
        $model = $this->findConversation($conversation);

        $message = $this->tenancy->run($model->tenant, function () use ($model, $operator, $validated) {
            $message = $model->messages()->create([
                'sender_id' => $operator->getKey(),
                'role' => SupportMessage::ROLE_PLATFORM,
                'body' => trim($validated['message']),
            ]);

            $model->update([
                'status' => SupportConversation::STATUS_ANSWERED,
                'last_message_at' => now(),
                'closed_at' => null,
            ]);
            $model->supportRequest()->update([
                'status' => SupportRequest::STATUS_IN_PROGRESS,
                'assigned_to' => $operator->getKey(),
            ]);

            return $message;
        });

        return response()->json($message->load('sender:id,name'), 201);
    }

    public function updateRequest(Request $request, int $conversation): JsonResponse
    {
        $operator = $this->platformUser($request);
        $validated = $request->validate([
            'status' => ['required', Rule::in([
                SupportRequest::STATUS_OPEN,
                SupportRequest::STATUS_IN_PROGRESS,
                SupportRequest::STATUS_RESOLVED,
                SupportRequest::STATUS_CLOSED,
            ])],
            'priority' => ['sometimes', Rule::in(['low', 'normal', 'high'])],
            'type' => ['sometimes', Rule::in(SupportRequest::TYPES)],
        ]);
        $model = $this->findConversation($conversation);

        $supportRequest = $this->tenancy->run($model->tenant, function () use ($model, $operator, $validated) {
            $supportRequest = $model->supportRequest()->firstOrFail();
            $supportRequest->fill($validated);
            $supportRequest->assigned_to ??= $operator->getKey();
            $supportRequest->resolved_at = in_array(
                $validated['status'],
                [SupportRequest::STATUS_RESOLVED, SupportRequest::STATUS_CLOSED],
                true
            ) ? now() : null;
            $supportRequest->save();

            if ($validated['status'] === SupportRequest::STATUS_CLOSED) {
                $model->update([
                    'status' => SupportConversation::STATUS_CLOSED,
                    'closed_at' => now(),
                ]);
            }

            return $supportRequest;
        });

        return response()->json($supportRequest);
    }

    private function findConversation(int $id): SupportConversation
    {
        return $this->tenancy->runWithoutScope(
            fn () => SupportConversation::query()
                ->with([
                    'tenant',
                    'user:id,name,email',
                    'messages.sender:id,name',
                    'supportRequest.assignee:id,name',
                ])
                ->findOrFail($id)
        );
    }

    private function platformUser(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401, 'Autenticación requerida.');
        abort_unless($user->isPlatformSuperadmin(), 403, 'Acceso reservado a plataforma.');

        return $user;
    }
}
