<?php

namespace App\Services\Onboarding;

use App\Models\OnboardingNote;
use App\Models\OnboardingStatusEvent;
use App\Models\Tenant;
use App\Models\TenantOnboarding;
use App\Models\User;
use App\Services\Modules\TenantFeatureService;
use App\Services\PlatformAuditLogger;
use App\Services\Tenancy\TenantProvisioningService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AssistedOnboardingService
{
    public function __construct(
        private readonly QuestionnaireClassifier $classifier,
        private readonly TenantProvisioningService $provisioning,
        private readonly TenantFeatureService $features,
        private readonly PlatformAuditLogger $audit,
    ) {
    }

    public function saveDraft(TenantOnboarding $onboarding, array $answers): TenantOnboarding
    {
        $onboarding->update([
            'step' => 'questionnaire',
            'status' => $onboarding->isSubmitted() ? $onboarding->status : 'questionnaire_pending',
            'draft' => array_merge($onboarding->draft ?? [], ['answers' => $answers]),
        ]);

        return $onboarding->fresh();
    }

    public function submit(TenantOnboarding $onboarding, array $answers): TenantOnboarding
    {
        $suggestion = $this->classifier->classify($answers);

        return DB::transaction(function () use ($onboarding, $answers, $suggestion) {
            $from = $onboarding->status;
            $onboarding->update([
                'step' => 'waiting',
                'status' => 'review_pending',
                'draft' => array_merge($onboarding->draft ?? [], ['answers' => $answers, 'suggestion' => $suggestion]),
                'submitted_at' => $onboarding->submitted_at ?? now(),
                'suggested_profile' => $suggestion['profile'],
                'suggested_modules' => $suggestion['modules'],
                'next_action' => 'Revisar cuestionario y proponer configuración',
                'customer_message' => config('onboarding.customer_copy.review_pending'),
            ]);

            $this->recordStatus($onboarding, $from, 'review_pending', 'Cuestionario enviado por el cliente.');

            return $onboarding->fresh();
        });
    }

    public function transition(
        Request $request,
        TenantOnboarding $onboarding,
        string $status,
        ?string $comment = null,
        ?string $nextAction = null,
        ?string $customerMessage = null,
        ?int $assigneeId = null,
    ): TenantOnboarding {
        if (! in_array($status, TenantOnboarding::STATUSES, true)) {
            throw ValidationException::withMessages(['status' => 'Estado inválido.']);
        }

        return DB::transaction(function () use ($request, $onboarding, $status, $comment, $nextAction, $customerMessage, $assigneeId) {
            $from = $onboarding->status;
            $onboarding->update([
                'status' => $status,
                'last_status_comment' => $comment,
                'next_action' => $nextAction,
                'customer_message' => $customerMessage ?: config('onboarding.customer_copy.'.$status),
                'assigned_to_user_id' => $assigneeId ?? $onboarding->assigned_to_user_id,
                'step' => $status === 'active' ? 'complete' : 'waiting',
                'completed_at' => $status === 'active' ? now() : $onboarding->completed_at,
            ]);

            if ($status === 'active' && $onboarding->tenant) {
                $this->provisioning->activate($onboarding->fresh());
            }

            $this->recordStatus($onboarding->fresh(), $from, $status, $comment, $nextAction);
            $this->audit->log(
                $request,
                'platform.onboarding.status_changed',
                $onboarding->tenant,
                $onboarding,
                ['status' => $from],
                ['status' => $status, 'next_action' => $nextAction],
            );

            return $onboarding->fresh();
        });
    }

    public function provisionTenant(
        Request $request,
        TenantOnboarding $onboarding,
        array $attributes,
        array $modules,
    ): Tenant {
        $owner = $onboarding->user;
        abort_unless($owner, 409, 'El caso no tiene un contacto dueño.');

        return DB::transaction(function () use ($request, $onboarding, $attributes, $modules, $owner) {
            $tenant = $this->provisioning->provisionProfile($owner, $onboarding, [
                'name' => $attributes['name'],
                'slug' => $attributes['slug'],
                'rubro_profile' => $attributes['rubro_profile'],
                'timezone' => $attributes['timezone'] ?? 'America/Argentina/Buenos_Aires',
                'repository_url' => $attributes['repository_url'] ?? null,
                'core_version' => $attributes['core_version'] ?? null,
                'environment_url' => $attributes['environment_url'] ?? null,
                'delivery_notes' => $attributes['delivery_notes'] ?? null,
            ]);

            $this->features->sync($tenant, $modules, $request->user()?->getKey());
            $this->transition(
                $request,
                $onboarding->fresh(),
                'implementation',
                'Tenant multi-tenant creado y módulos asignados.',
                'Completar branding, usuarios y validación',
                config('onboarding.customer_copy.implementation'),
                $request->user()?->getKey(),
            );

            $this->audit->log(
                $request,
                'platform.onboarding.tenant_provisioned',
                $tenant,
                $onboarding,
                after: [
                    'slug' => $tenant->slug,
                    'modules' => $modules,
                    'repository_url' => $tenant->repository_url,
                ],
            );

            return $tenant;
        });
    }

    public function addNote(Request $request, TenantOnboarding $onboarding, string $body, bool $internal = true): OnboardingNote
    {
        $note = $onboarding->notes()->create([
            'user_id' => $request->user()->getKey(),
            'body' => $body,
            'is_internal' => $internal,
        ]);

        $this->audit->log(
            $request,
            'platform.onboarding.note_added',
            $onboarding->tenant,
            $onboarding,
            after: ['internal' => $internal],
        );

        return $note;
    }

    public function updateDelivery(Request $request, Tenant $tenant, array $attributes): Tenant
    {
        $before = $tenant->only(['repository_url', 'core_version', 'environment_url', 'delivery_notes']);
        $tenant->fill($attributes)->save();

        $this->audit->log(
            $request,
            'platform.tenant.delivery_updated',
            $tenant,
            $tenant,
            $before,
            $tenant->only(['repository_url', 'core_version', 'environment_url', 'delivery_notes']),
        );

        return $tenant->fresh();
    }

    public function suggestedSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'comercio';
        $slug = $base;
        $i = 1;

        while (Tenant::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    private function recordStatus(
        TenantOnboarding $onboarding,
        ?string $from,
        string $to,
        ?string $comment = null,
        ?string $nextAction = null,
    ): void {
        OnboardingStatusEvent::query()->create([
            'tenant_onboarding_id' => $onboarding->getKey(),
            'actor_user_id' => auth()->id(),
            'from_status' => $from,
            'to_status' => $to,
            'comment' => $comment,
            'next_action' => $nextAction,
        ]);
    }
}
