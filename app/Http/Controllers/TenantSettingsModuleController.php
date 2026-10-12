<?php

namespace App\Http\Controllers;

use App\Services\Modules\TenantFeatureService;
use App\Services\PlatformAuditLogger;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TenantSettingsModuleController extends Controller
{
    public function __construct(
        private readonly TenantContext $context,
        private readonly TenantFeatureService $features,
        private readonly PlatformAuditLogger $audit,
    ) {
    }

    public function index(): JsonResponse
    {
        $tenant = $this->requireTenant();

        return response()->json([
            'modules' => $this->features->tenantCatalog($tenant),
            'families' => config('modules.families', []),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $tenant = $this->requireTenant();
        $this->features->catalog();

        $validated = $request->validate([
            'modules' => ['required', 'array'],
            'modules.*' => ['string', Rule::exists('platform_modules', 'key')],
        ]);

        $requested = array_values(array_unique($validated['modules']));
        $before = $tenant->moduleGrants()
            ->where('enabled', true)
            ->pluck('module_key')
            ->all();

        DB::transaction(function () use ($request, $tenant, $requested, $before): void {
            $this->features->syncFromSettings(
                $tenant,
                $requested,
                $request->user()?->getAuthIdentifier()
            );

            $after = $tenant->moduleGrants()
                ->where('enabled', true)
                ->pluck('module_key')
                ->all();

            sort($before);
            sort($after);

            if ($before === $after) {
                return;
            }

            $this->audit->log(
                $request,
                'tenant.settings.modules_updated',
                $tenant,
                $tenant,
                ['modules' => $before],
                ['modules' => $after],
            );
        });

        $tenant->refresh();

        return response()->json([
            'message' => 'Módulos actualizados.',
            'modules' => $this->features->tenantCatalog($tenant),
            'families' => config('modules.families', []),
        ]);
    }

    private function requireTenant(): \App\Models\Tenant
    {
        abort_unless($this->context->hasTenant(), 409, 'Seleccioná un comercio para gestionar módulos.');

        return $this->context->tenant();
    }
}
