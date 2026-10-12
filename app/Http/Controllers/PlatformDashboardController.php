<?php

namespace App\Http\Controllers;

use App\Models\PlatformAuditEvent;
use App\Models\Tenant;
use App\Models\TenantUser;
use Illuminate\Contracts\View\View;

class PlatformDashboardController extends Controller
{
    public function index(): View
    {
        $tenantCounts = Tenant::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('platform.dashboard', [
            'metrics' => [
                'tenants' => $tenantCounts->sum(),
                'activeTenants' => (int) $tenantCounts->get('active', 0),
                'suspendedTenants' => (int) $tenantCounts->get('suspended', 0),
                'memberships' => TenantUser::query()->count(),
                'eventsToday' => PlatformAuditEvent::query()
                    ->whereDate('created_at', now()->toDateString())
                    ->count(),
            ],
            'recentTenants' => Tenant::query()
                ->withCount('users')
                ->latest()
                ->limit(6)
                ->get(),
            'recentEvents' => PlatformAuditEvent::query()
                ->with(['tenant:id,name', 'actor:id,name'])
                ->latest()
                ->limit(8)
                ->get(),
        ]);
    }
}
