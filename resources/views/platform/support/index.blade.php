<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inbox de soporte</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <main class="mx-auto max-w-7xl p-6">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold">Inbox de soporte</h1>
                <p class="text-sm text-slate-600">Consultas de todos los tenants.</p>
            </div>
            <form method="GET" action="/platform/support" class="flex flex-wrap gap-2">
                <select name="status" class="rounded border-slate-300 text-sm">
                    <option value="">Todos los estados</option>
                    @foreach (['open', 'in_progress', 'resolved', 'closed'] as $status)
                        <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>
                            {{ str_replace('_', ' ', ucfirst($status)) }}
                        </option>
                    @endforeach
                </select>
                <select name="type" class="rounded border-slate-300 text-sm">
                    <option value="">Todos los tipos</option>
                    @foreach (['support', 'bug', 'feature_request'] as $type)
                        <option value="{{ $type }}" @selected(($filters['type'] ?? '') === $type)>
                            {{ str_replace('_', ' ', ucfirst($type)) }}
                        </option>
                    @endforeach
                </select>
                <button class="rounded bg-slate-800 px-4 py-2 text-sm font-semibold text-white">Filtrar</button>
            </form>
        </div>

        <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Prioridad</th>
                            <th class="px-4 py-3">Tipo</th>
                            <th class="px-4 py-3">Tenant / usuario</th>
                            <th class="px-4 py-3">Asunto</th>
                            <th class="px-4 py-3">Estado</th>
                            <th class="px-4 py-3">Actualizado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($requests as $supportRequest)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $supportRequest->priority === 'high' ? 'bg-red-100 text-red-700' : 'bg-slate-100 text-slate-700' }}">
                                        {{ $supportRequest->priority }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">{{ str_replace('_', ' ', $supportRequest->type) }}</td>
                                <td class="px-4 py-3">
                                    <div class="font-medium">{{ $supportRequest->conversation->tenant->name }}</div>
                                    <div class="text-xs text-slate-500">{{ $supportRequest->conversation->user->email }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <a class="font-medium text-blue-700 hover:underline" href="/platform/support/{{ $supportRequest->support_conversation_id }}">
                                        {{ $supportRequest->title }}
                                    </a>
                                </td>
                                <td class="px-4 py-3">{{ str_replace('_', ' ', $supportRequest->status) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $supportRequest->updated_at->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-12 text-center text-slate-500">No hay solicitudes para estos filtros.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-5">{{ $requests->links() }}</div>
    </main>
</body>
</html>
