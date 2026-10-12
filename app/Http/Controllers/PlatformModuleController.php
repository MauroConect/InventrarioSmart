<?php

namespace App\Http\Controllers;

use App\Models\PlatformModule;
use App\Models\Tenant;
use App\Services\Modules\TenantFeatureService;
use App\Services\PlatformAuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PlatformModuleController extends Controller
{
    public function __construct(
        private readonly PlatformAuditLogger $audit,
        private readonly TenantFeatureService $features,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        return view('platform.modules.index', [
            'modules' => PlatformModule::query()
                ->where('is_visible', true)
                ->orderBy('sort_order')
                ->get(),
            'tenants' => Tenant::query()
                ->with('moduleGrants')
                ->when($filters['search'] ?? null, function ($query, string $search): void {
                    $query->where(function ($query) use ($search): void {
                        $query
                            ->where('name', 'like', '%'.$search.'%')
                            ->orWhere('slug', 'like', '%'.$search.'%');
                    });
                })
                ->orderBy('name')
                ->paginate(15)
                ->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'modules' => ['nullable', 'array'],
            'modules.*' => ['string', Rule::exists('platform_modules', 'key')],
        ]);

        $enabled = array_values(array_unique($validated['modules'] ?? []));
        $before = $tenant->moduleGrants()
            ->where('enabled', true)
            ->pluck('module_key')
            ->all();
        sort($enabled);
        sort($before);

        if ($before === $enabled) {
            return back()->with('status', 'Los grants de módulos no cambiaron.');
        }

        DB::transaction(function () use ($request, $tenant, $enabled, $before): void {
            $this->features->sync($tenant, $enabled, $request->user()?->getAuthIdentifier());

            $this->audit->log(
                $request,
                'platform.tenant.modules_updated',
                $tenant,
                $tenant,
                ['modules' => $before],
                ['modules' => $enabled],
            );
        });

        return back()->with('success', 'Grants de módulos actualizados.');
    }
}
