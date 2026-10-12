@extends('marketing.layout')

@section('content')
<main class="mx-auto max-w-4xl px-6 py-12">
    <div class="mb-8">
        <p class="text-sm font-semibold uppercase tracking-wider text-blue-300">Onboarding asistido</p>
        <h2 class="mt-2 text-2xl font-bold text-white">InventarioSmart configura tu comercio</h2>
    </div>
    <section class="rounded-3xl bg-white p-6 text-slate-900 shadow-2xl md:p-10">
        @if(session('success'))<div class="mb-5 rounded-lg bg-green-50 p-3 text-sm text-green-700">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="mb-5 rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</div>@endif
        @yield('onboarding-content')
    </section>
</main>
@endsection
