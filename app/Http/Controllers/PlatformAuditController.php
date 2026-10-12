<?php

namespace App\Http\Controllers;

use App\Models\PlatformAuditEvent;
use App\Models\Tenant;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlatformAuditController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'tenant_id' => ['nullable', 'integer', Rule::exists('tenants', 'id')],
            'action' => ['nullable', 'string', 'max:255'],
            'correlation_id' => ['nullable', 'uuid'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $events = PlatformAuditEvent::query()
            ->with(['tenant:id,name', 'actor:id,name'])
            ->when(
                $filters['tenant_id'] ?? null,
                fn ($query, int $tenantId) => $query->where('tenant_id', $tenantId)
            )
            ->when(
                $filters['action'] ?? null,
                fn ($query, string $action) => $query->where('action', $action)
            )
            ->when(
                $filters['correlation_id'] ?? null,
                fn ($query, string $correlationId) => $query->where('correlation_id', $correlationId)
            )
            ->when(
                $filters['date_from'] ?? null,
                fn ($query, string $date) => $query->whereDate('created_at', '>=', $date)
            )
            ->when(
                $filters['date_to'] ?? null,
                fn ($query, string $date) => $query->whereDate('created_at', '<=', $date)
            )
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('platform.audit.index', [
            'events' => $events,
            'filters' => $filters,
            'tenants' => Tenant::query()->orderBy('name')->get(['id', 'name']),
            'actions' => PlatformAuditEvent::query()
                ->distinct()
                ->orderBy('action')
                ->pluck('action'),
        ]);
    }
}
