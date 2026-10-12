<?php

namespace App\Http\Controllers;

use App\Models\PlatformAuditEvent;
use App\Models\PlatformModule;
use App\Models\Tenant;
use App\Services\PlatformAuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PlatformTenantController extends Controller
{
    public function __construct(
        private readonly PlatformAuditLogger $audit,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['active', 'suspended'])],
        ]);

        $tenants = Tenant::query()
            ->withCount('users')
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('slug', 'like', '%'.$search.'%')
                        ->orWhere('uuid', $search);
                });
            })
            ->when(
                $filters['status'] ?? null,
                fn ($query, string $status) => $query->where('status', $status)
            )
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('platform.tenants.index', [
            'tenants' => $tenants,
            'filters' => $filters,
        ]);
    }

    public function show(Tenant $tenant): View
    {
        $tenant->load([
            'memberships' => fn ($query) => $query
                ->with('user:id,name,email')
                ->orderBy('created_at'),
            'moduleGrants',
        ])->loadCount('users');

        return view('platform.tenants.show', [
            'tenant' => $tenant,
            'modules' => PlatformModule::query()
                ->where('is_visible', true)
                ->orderBy('sort_order')
                ->get(),
            'events' => PlatformAuditEvent::query()
                ->with('actor:id,name')
                ->where('tenant_id', $tenant->id)
                ->latest()
                ->limit(20)
                ->get(),
        ]);
    }

    public function suspend(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($tenant->status === 'suspended') {
            return back()->with('status', 'El comercio ya estaba suspendido.');
        }

        DB::transaction(function () use ($request, $tenant, $validated): void {
            $before = [
                'status' => $tenant->status,
                'suspended_at' => $tenant->suspended_at,
                'suspension_reason' => $tenant->suspension_reason,
            ];

            $tenant->update([
                'status' => 'suspended',
                'suspended_at' => now(),
                'suspension_reason' => $validated['reason'] ?? null,
            ]);

            $this->audit->log(
                $request,
                'platform.tenant.suspended',
                $tenant,
                $tenant,
                $before,
                [
                    'status' => 'suspended',
                    'suspended_at' => $tenant->suspended_at,
                    'suspension_reason' => $tenant->suspension_reason,
                ],
            );
        });

        return back()->with('success', 'Comercio suspendido.');
    }

    public function reactivate(Request $request, Tenant $tenant): RedirectResponse
    {
        if ($tenant->status === 'active') {
            return back()->with('status', 'El comercio ya estaba activo.');
        }

        DB::transaction(function () use ($request, $tenant): void {
            $before = [
                'status' => $tenant->status,
                'suspended_at' => $tenant->suspended_at,
                'suspension_reason' => $tenant->suspension_reason,
            ];

            $tenant->update([
                'status' => 'active',
                'suspended_at' => null,
                'suspension_reason' => null,
            ]);

            $this->audit->log(
                $request,
                'platform.tenant.reactivated',
                $tenant,
                $tenant,
                $before,
                [
                    'status' => 'active',
                    'suspended_at' => null,
                    'suspension_reason' => null,
                ],
            );
        });

        return back()->with('success', 'Comercio reactivado.');
    }

    public function updateDelivery(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'repository_url' => ['nullable', 'url', 'max:255'],
            'core_version' => ['nullable', 'string', 'max:40'],
            'environment_url' => ['nullable', 'url', 'max:255'],
            'delivery_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $before = $tenant->only(['repository_url', 'core_version', 'environment_url', 'delivery_notes']);
        $tenant->update($validated);

        $this->audit->log(
            $request,
            'platform.tenant.delivery_updated',
            $tenant,
            $tenant,
            $before,
            $tenant->only(['repository_url', 'core_version', 'environment_url', 'delivery_notes']),
        );

        return back()->with('success', 'Repositorio y entrega actualizados.');
    }
}
