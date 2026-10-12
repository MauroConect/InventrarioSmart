<?php

namespace App\Http\Controllers;

use App\Models\PlatformModule;
use App\Models\Tenant;
use App\Models\TenantOnboarding;
use App\Models\TenantUser;
use App\Models\User;
use App\Services\Modules\TenantFeatureService;
use App\Services\Onboarding\AssistedOnboardingService;
use App\Services\PlatformAuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PlatformOnboardingController extends Controller
{
    public function __construct(
        private readonly PlatformAuditLogger $audit,
        private readonly TenantFeatureService $features,
        private readonly AssistedOnboardingService $assisted,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(TenantOnboarding::STATUSES)],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $cases = TenantOnboarding::query()
            ->with(['user:id,name,email', 'tenant:id,name,slug,status', 'assignee:id,name'])
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->whereHas('user', function ($query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            })
            ->latest('updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('platform.onboarding.index', [
            'cases' => $cases,
            'filters' => $filters,
            'statuses' => config('onboarding.statuses'),
        ]);
    }

    public function show(TenantOnboarding $onboarding): View
    {
        $onboarding->load(['user', 'tenant.moduleGrants', 'assignee', 'notes.author', 'events.actor']);

        return view('platform.onboarding.show', [
            'onboarding' => $onboarding,
            'answers' => $onboarding->answers(),
            'modules' => $this->features->catalog(),
            'statuses' => config('onboarding.statuses'),
            'suggestedSlug' => $this->assisted->suggestedSlug($onboarding->answers()['business_name'] ?? $onboarding->user?->name ?? 'comercio'),
        ]);
    }

    public function updateStatus(Request $request, TenantOnboarding $onboarding): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(TenantOnboarding::STATUSES)],
            'comment' => ['nullable', 'string', 'max:1000'],
            'next_action' => ['nullable', 'string', 'max:255'],
            'customer_message' => ['nullable', 'string', 'max:1000'],
            'assigned_to_user_id' => ['nullable', 'exists:users,id'],
        ]);

        $this->assisted->transition(
            $request,
            $onboarding,
            $validated['status'],
            $validated['comment'] ?? null,
            $validated['next_action'] ?? null,
            $validated['customer_message'] ?? null,
            isset($validated['assigned_to_user_id']) ? (int) $validated['assigned_to_user_id'] : null,
        );

        return back()->with('success', 'Estado de onboarding actualizado.');
    }

    public function provision(Request $request, TenantOnboarding $onboarding): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('tenants', 'slug')->ignore($onboarding->tenant_id)],
            'rubro_profile' => ['required', 'string', 'max:100'],
            'timezone' => ['required', 'timezone'],
            'modules' => ['nullable', 'array'],
            'modules.*' => ['string'],
            'repository_url' => ['nullable', 'url', 'max:255'],
            'core_version' => ['nullable', 'string', 'max:40'],
            'environment_url' => ['nullable', 'url', 'max:255'],
            'delivery_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->assisted->provisionTenant(
            $request,
            $onboarding,
            $validated,
            array_values(array_unique($validated['modules'] ?? $onboarding->suggested_modules ?? [])),
        );

        return back()->with('success', 'Tenant creado y módulos asignados. El cliente todavía no entra al sistema hasta que lo actives.');
    }

    public function storeNote(Request $request, TenantOnboarding $onboarding): RedirectResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:4000'],
            'is_internal' => ['nullable', 'boolean'],
        ]);

        $this->assisted->addNote($request, $onboarding, $validated['body'], $request->boolean('is_internal', true));

        return back()->with('success', 'Nota registrada.');
    }

    public function create(): View
    {
        return view('platform.onboarding.create', [
            'modules' => PlatformModule::query()
                ->where('is_visible', true)
                ->orderBy('sort_order')
                ->get(),
            'defaultTimezone' => config('app.timezone', 'UTC'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('tenants', 'slug'),
            ],
            'rubro_profile' => ['required', 'string', 'max:100'],
            'timezone' => ['required', 'timezone'],
            'owner_name' => ['nullable', 'string', 'max:255'],
            'owner_email' => ['required', 'email', 'max:255'],
            'owner_password' => ['nullable', 'string', 'min:8', 'max:72'],
            'modules' => ['nullable', 'array'],
            'modules.*' => ['string', Rule::exists('platform_modules', 'key')],
        ]);

        $tenant = DB::transaction(function () use ($request, $validated): Tenant {
            $enabledModules = array_values(array_unique($validated['modules'] ?? []));
            sort($enabledModules);

            $owner = User::query()
                ->where('email', $validated['owner_email'])
                ->first();
            $ownerWasCreated = false;

            if (! $owner) {
                if (empty($validated['owner_name']) || empty($validated['owner_password'])) {
                    throw ValidationException::withMessages([
                        'owner_name' => 'El nombre y la contraseña son obligatorios para una identidad nueva.',
                    ]);
                }

                $owner = User::query()->create([
                    'name' => $validated['owner_name'],
                    'email' => $validated['owner_email'],
                    'password' => $validated['owner_password'],
                    'role' => User::ROLE_ADMIN,
                ]);
                $ownerWasCreated = true;
            }

            $tenant = Tenant::query()->create([
                'owner_user_id' => $owner->id,
                'slug' => $validated['slug'],
                'name' => $validated['name'],
                'rubro_profile' => $validated['rubro_profile'],
                'features' => [],
                'status' => 'active',
                'timezone' => $validated['timezone'],
                'activated_at' => now(),
                'onboarding_completed_at' => now(),
            ]);

            if ($ownerWasCreated) {
                $this->audit->log(
                    $request,
                    'platform.user.created_for_onboarding',
                    $tenant,
                    $owner,
                    after: [
                        'name' => $owner->name,
                        'email' => $owner->email,
                    ],
                );
            }

            $membership = TenantUser::query()->create([
                'tenant_id' => $tenant->id,
                'user_id' => $owner->id,
                'role' => 'owner',
                'is_default' => $owner->memberships()->doesntExist(),
                'status' => 'active',
            ]);

            $this->audit->log(
                $request,
                'platform.tenant.created',
                $tenant,
                $tenant,
                after: [
                    'name' => $tenant->name,
                    'slug' => $tenant->slug,
                    'status' => $tenant->status,
                    'rubro_profile' => $tenant->rubro_profile,
                    'timezone' => $tenant->timezone,
                    'owner_user_id' => $owner->id,
                ],
            );

            $this->audit->log(
                $request,
                'platform.membership.created',
                $tenant,
                $membership,
                after: [
                    'user_id' => $owner->id,
                    'role' => $membership->role,
                    'status' => $membership->status,
                    'is_default' => $membership->is_default,
                ],
            );

            $this->features->sync($tenant, $enabledModules, $request->user()?->getAuthIdentifier());

            $this->audit->log(
                $request,
                'platform.tenant.modules_updated',
                $tenant,
                $tenant,
                ['modules' => []],
                ['modules' => $enabledModules],
            );

            return $tenant;
        });

        return redirect('/platform/tenants/'.$tenant->getRouteKey())
            ->with('success', 'Comercio creado y propietario vinculado.');
    }
}
