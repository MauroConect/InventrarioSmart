@extends('layouts.platform')

@section('title', 'Onboarding')
@section('page-title', 'Pipeline de onboarding')

@section('actions')
    <a href="{{ url('/platform/onboarding/create') }}" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Alta manual</a>
@endsection

@section('content')
    <form method="GET" class="mb-6 flex flex-wrap gap-3">
        <input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Buscar contacto" class="rounded-lg border px-3 py-2">
        <select name="status" class="rounded-lg border px-3 py-2">
            <option value="">Todos los estados</option>
            @foreach($statuses as $value => $label)
                <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <button class="rounded-lg bg-cyan-600 px-4 py-2 text-sm font-semibold text-white">Filtrar</button>
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <table class="min-w-full divide-y text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-6 py-3">Contacto</th>
                    <th class="px-6 py-3">Comercio</th>
                    <th class="px-6 py-3">Estado</th>
                    <th class="px-6 py-3">Próxima acción</th>
                    <th class="px-6 py-3">Responsable</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($cases as $case)
                    <tr>
                        <td class="px-6 py-4">
                            <a href="{{ url('/platform/onboarding/'.$case->id) }}" class="font-semibold text-cyan-800">{{ $case->user?->name }}</a>
                            <p class="text-xs text-slate-500">{{ $case->user?->email }}</p>
                        </td>
                        <td class="px-6 py-4">{{ $case->answers()['business_name'] ?? $case->tenant?->name ?? '—' }}</td>
                        <td class="px-6 py-4">{{ $statuses[$case->status] ?? $case->status }}</td>
                        <td class="px-6 py-4">{{ $case->next_action ?? '—' }}</td>
                        <td class="px-6 py-4">{{ $case->assignee?->name ?? 'Sin asignar' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-6 py-10 text-center text-slate-500">Todavía no hay casos.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $cases->links() }}</div>
@endsection
