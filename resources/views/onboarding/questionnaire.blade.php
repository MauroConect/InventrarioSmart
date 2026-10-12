@extends('onboarding.layout')
@section('title', 'Contanos sobre tu comercio')
@section('onboarding-content')
@php($a = $answers)
<h1 class="text-3xl font-black">Contanos cómo trabaja tu comercio</h1>
<p class="mt-2 text-slate-500">No tenés que elegir módulos ni configurar el sistema. Nosotros armamos el tenant y te avisamos cuando esté listo.</p>

<form method="POST" action="{{ route('onboarding.store') }}" class="mt-8 space-y-8">
    @csrf

    <section class="space-y-4">
        <h2 class="text-lg font-bold">Datos del comercio</h2>
        <div class="grid gap-4 md:grid-cols-2">
            <label class="block md:col-span-2"><span class="text-sm font-semibold">Nombre comercial</span><input name="business_name" value="{{ old('business_name', $a['business_name'] ?? '') }}" required class="mt-1 w-full rounded-lg border px-3 py-2"></label>
            <label class="block"><span class="text-sm font-semibold">Localidad</span><input name="locality" value="{{ old('locality', $a['locality'] ?? '') }}" required class="mt-1 w-full rounded-lg border px-3 py-2"></label>
            <label class="block"><span class="text-sm font-semibold">Teléfono</span><input name="contact_phone" value="{{ old('contact_phone', $a['contact_phone'] ?? '') }}" class="mt-1 w-full rounded-lg border px-3 py-2"></label>
            <label class="block"><span class="text-sm font-semibold">Rubro</span>
                <select name="rubro" class="mt-1 w-full rounded-lg border px-3 py-2">
                    @foreach($rubros as $value => $label)
                        <option value="{{ $value }}" @selected(old('rubro', $a['rubro'] ?? 'base') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block"><span class="text-sm font-semibold">Actividades secundarias</span><input name="secondary_activities" value="{{ old('secondary_activities', $a['secondary_activities'] ?? '') }}" class="mt-1 w-full rounded-lg border px-3 py-2"></label>
            <label class="block"><span class="text-sm font-semibold">Sucursales</span><input type="number" min="1" name="branches_count" value="{{ old('branches_count', $a['branches_count'] ?? 1) }}" required class="mt-1 w-full rounded-lg border px-3 py-2"></label>
            <label class="block"><span class="text-sm font-semibold">Cajas</span><input type="number" min="1" name="cash_registers_count" value="{{ old('cash_registers_count', $a['cash_registers_count'] ?? 1) }}" required class="mt-1 w-full rounded-lg border px-3 py-2"></label>
            <label class="block"><span class="text-sm font-semibold">Usuarios</span><input type="number" min="1" name="users_count" value="{{ old('users_count', $a['users_count'] ?? 1) }}" required class="mt-1 w-full rounded-lg border px-3 py-2"></label>
            <label class="block"><span class="text-sm font-semibold">Fecha deseada de puesta en marcha</span><input type="date" name="desired_go_live" value="{{ old('desired_go_live', $a['desired_go_live'] ?? '') }}" class="mt-1 w-full rounded-lg border px-3 py-2"></label>
        </div>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-bold">Volumen y operación</h2>
        <div class="grid gap-4 md:grid-cols-2">
            <label class="block"><span class="text-sm font-semibold">Ventas al mes (aprox.)</span><input type="number" min="0" name="monthly_sales" value="{{ old('monthly_sales', $a['monthly_sales'] ?? 0) }}" required class="mt-1 w-full rounded-lg border px-3 py-2"></label>
            <label class="block"><span class="text-sm font-semibold">Productos o servicios</span><input type="number" min="0" name="products_count" value="{{ old('products_count', $a['products_count'] ?? 0) }}" required class="mt-1 w-full rounded-lg border px-3 py-2"></label>
            <label class="block md:col-span-2"><span class="text-sm font-semibold">Horarios o picos</span><input name="peak_hours" value="{{ old('peak_hours', $a['peak_hours'] ?? '') }}" class="mt-1 w-full rounded-lg border px-3 py-2"></label>
        </div>
        <fieldset>
            <legend class="text-sm font-semibold">¿Cómo vendés?</legend>
            <div class="mt-2 flex flex-wrap gap-4 text-sm">
                @foreach(['unit' => 'Por unidad', 'weight' => 'Por peso', 'variants' => 'Con variantes', 'combos' => 'Combos'] as $value => $label)
                    <label class="inline-flex items-center gap-2"><input type="checkbox" name="sale_types[]" value="{{ $value }}" @checked(in_array($value, old('sale_types', $a['sale_types'] ?? []), true))> {{ $label }}</label>
                @endforeach
            </div>
        </fieldset>
        <fieldset>
            <legend class="text-sm font-semibold">Periféricos</legend>
            <div class="mt-2 flex flex-wrap gap-4 text-sm">
                @foreach(['scanner' => 'Scanner', 'scale' => 'Balanza', 'printer' => 'Impresora'] as $value => $label)
                    <label class="inline-flex items-center gap-2"><input type="checkbox" name="peripherals[]" value="{{ $value }}" @checked(in_array($value, old('peripherals', $a['peripherals'] ?? []), true))> {{ $label }}</label>
                @endforeach
            </div>
        </fieldset>
        <div class="grid gap-4 md:grid-cols-3">
            <label class="block"><span class="text-sm font-semibold">¿Manejan stock?</span><select name="stock_management" class="mt-1 w-full rounded-lg border px-3 py-2">@foreach(['yes'=>'Sí','partial'=>'Parcial','no'=>'No'] as $value=>$label)<option value="{{ $value }}" @selected(old('stock_management', $a['stock_management'] ?? 'yes') === $value)>{{ $label }}</option>@endforeach</select></label>
            <label class="block"><span class="text-sm font-semibold">¿Ventas abiertas? (bar, restó, heladería)</span><select name="open_orders" class="mt-1 w-full rounded-lg border px-3 py-2">@foreach(['no'=>'No','yes'=>'Sí'] as $value=>$label)<option value="{{ $value }}" @selected(old('open_orders', $a['open_orders'] ?? 'no') === $value)>{{ $label }}</option>@endforeach</select></label>
            <label class="block"><span class="text-sm font-semibold">¿Mesas o comandas?</span><select name="uses_tables" class="mt-1 w-full rounded-lg border px-3 py-2">@foreach(['no'=>'No','yes'=>'Sí'] as $value=>$label)<option value="{{ $value }}" @selected(old('uses_tables', $a['uses_tables'] ?? 'no') === $value)>{{ $label }}</option>@endforeach</select></label>
            <label class="block"><span class="text-sm font-semibold">¿Atienden con turnos / agenda?</span><select name="appointments" class="mt-1 w-full rounded-lg border px-3 py-2">@foreach(['no'=>'No','yes'=>'Sí'] as $value=>$label)<option value="{{ $value }}" @selected(old('appointments', $a['appointments'] ?? 'no') === $value)>{{ $label }}</option>@endforeach</select></label>
            <label class="block"><span class="text-sm font-semibold">¿Identifican clientes?</span><select name="identify_customers" class="mt-1 w-full rounded-lg border px-3 py-2">@foreach(['no'=>'No','yes'=>'Sí'] as $value=>$label)<option value="{{ $value }}" @selected(old('identify_customers', $a['identify_customers'] ?? 'no') === $value)>{{ $label }}</option>@endforeach</select></label>
        </div>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-bold">Cobros y fiscalidad</h2>
        <div class="grid gap-4 md:grid-cols-2">
            <label class="block"><span class="text-sm font-semibold">Medios de pago</span><input name="payment_methods" value="{{ old('payment_methods', $a['payment_methods'] ?? '') }}" class="mt-1 w-full rounded-lg border px-3 py-2"></label>
            <label class="block"><span class="text-sm font-semibold">Condición fiscal</span><input name="fiscal_condition" value="{{ old('fiscal_condition', $a['fiscal_condition'] ?? '') }}" class="mt-1 w-full rounded-lg border px-3 py-2"></label>
            <label class="block"><span class="text-sm font-semibold">¿Cuenta corriente?</span><select name="credit_accounts" class="mt-1 w-full rounded-lg border px-3 py-2">@foreach(['no'=>'No','yes'=>'Sí'] as $value=>$label)<option value="{{ $value }}" @selected(old('credit_accounts', $a['credit_accounts'] ?? 'no') === $value)>{{ $label }}</option>@endforeach</select></label>
            <label class="block"><span class="text-sm font-semibold">¿Cheques?</span><select name="cheques" class="mt-1 w-full rounded-lg border px-3 py-2">@foreach(['no'=>'No','yes'=>'Sí'] as $value=>$label)<option value="{{ $value }}" @selected(old('cheques', $a['cheques'] ?? 'no') === $value)>{{ $label }}</option>@endforeach</select></label>
            <label class="block md:col-span-2"><span class="text-sm font-semibold">¿Facturación electrónica?</span><select name="electronic_invoicing" class="mt-1 w-full rounded-lg border px-3 py-2">@foreach(['no'=>'No','yes'=>'Sí'] as $value=>$label)<option value="{{ $value }}" @selected(old('electronic_invoicing', $a['electronic_invoicing'] ?? 'no') === $value)>{{ $label }}</option>@endforeach</select></label>
        </div>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-bold">Situación actual</h2>
        <label class="block"><span class="text-sm font-semibold">¿Qué usan hoy?</span><input name="current_system" value="{{ old('current_system', $a['current_system'] ?? '') }}" class="mt-1 w-full rounded-lg border px-3 py-2"></label>
        <label class="block"><span class="text-sm font-semibold">Datos a migrar</span><textarea name="data_to_migrate" rows="2" class="mt-1 w-full rounded-lg border px-3 py-2">{{ old('data_to_migrate', $a['data_to_migrate'] ?? '') }}</textarea></label>
        <label class="block"><span class="text-sm font-semibold">Principales problemas</span><textarea name="pain_points" rows="2" class="mt-1 w-full rounded-lg border px-3 py-2">{{ old('pain_points', $a['pain_points'] ?? '') }}</textarea></label>
        <label class="block"><span class="text-sm font-semibold">Reportes indispensables</span><textarea name="required_reports" rows="2" class="mt-1 w-full rounded-lg border px-3 py-2">{{ old('required_reports', $a['required_reports'] ?? '') }}</textarea></label>
        <label class="block"><span class="text-sm font-semibold">Funciones especiales de tu negocio</span><textarea name="special_features" rows="3" class="mt-1 w-full rounded-lg border px-3 py-2" placeholder="Esto nos ayuda a decidir si va al producto común o a tu repositorio de features.">{{ old('special_features', $a['special_features'] ?? '') }}</textarea></label>
    </section>

    <div class="flex flex-wrap justify-end gap-3">
        <button name="intent" value="draft" class="rounded-lg border px-6 py-3 font-semibold text-slate-600">Guardar avance</button>
        <button name="intent" value="submit" class="rounded-lg bg-blue-600 px-6 py-3 font-bold text-white">Enviar al equipo</button>
    </div>
</form>
@endsection
