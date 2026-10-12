<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seleccionar comercio</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-100 flex items-center justify-center p-4">
    <main class="w-full max-w-lg rounded-lg bg-white p-6 shadow-lg">
        <h1 class="text-2xl font-bold text-gray-900">Seleccionar comercio</h1>
        <p class="mt-2 text-sm text-gray-600">Elegí el comercio con el que querés trabajar en esta sesión.</p>

        @if ($tenants->isEmpty())
            <div class="mt-6 rounded border border-amber-300 bg-amber-50 p-4 text-amber-900">
                Tu usuario no tiene comercios activos asignados.
            </div>
        @else
            <form method="POST" action="{{ route('tenant.select.store') }}" class="mt-6 space-y-4">
                @csrf
                <div class="space-y-2">
                    @foreach ($tenants as $tenant)
                        <label class="flex cursor-pointer items-center gap-3 rounded border p-3 hover:bg-gray-50">
                            <input
                                type="radio"
                                name="tenant_id"
                                value="{{ $tenant->id }}"
                                class="h-4 w-4"
                                required
                                @checked(old('tenant_id', $tenants->firstWhere('pivot.is_default', true)?->id) == $tenant->id)
                            >
                            <span>
                                <strong class="block text-gray-900">{{ $tenant->name }}</strong>
                                <span class="text-sm text-gray-500">{{ $tenant->rubro_profile }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>

                @error('tenant_id')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror

                <button class="w-full rounded bg-blue-600 px-4 py-2 font-semibold text-white hover:bg-blue-700">
                    Continuar
                </button>
            </form>
        @endif

        <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">
            @csrf
            <button class="text-sm text-gray-600 underline">Cerrar sesión</button>
        </form>
    </main>
</body>
</html>
