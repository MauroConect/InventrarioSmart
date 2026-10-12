<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantSelectionController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $tenants = $this->activeTenants($request);

        if ($tenants->count() === 1) {
            $request->session()->put(config('tenancy.session_key', 'tenant_id'), $tenants->first()->getKey());

            return redirect()->route('home');
        }

        if ($tenants->isEmpty() && config('tenancy.legacy_mode', true)) {
            return redirect()->route('home');
        }

        return view('auth.select-tenant', compact('tenants'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'integer'],
        ]);

        $tenant = $this->activeTenants($request)
            ->firstWhere('id', (int) $validated['tenant_id']);

        abort_unless($tenant, 403, 'No tienes acceso al comercio solicitado.');

        $request->session()->put(config('tenancy.session_key', 'tenant_id'), $tenant->getKey());

        return redirect()->intended(route('home'));
    }

    private function activeTenants(Request $request)
    {
        return $request->user()
            ->tenants()
            ->wherePivot('status', config('tenancy.active_status', 'active'))
            ->where('tenants.status', config('tenancy.active_status', 'active'))
            ->orderByDesc('tenant_user.is_default')
            ->orderBy('tenants.name')
            ->get();
    }
}
