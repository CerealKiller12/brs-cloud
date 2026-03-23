@extends('layouts.app', ['title' => 'Onboarding | BRS Cloud'])

@section('content')
<section class="hero">
    <small>Onboarding</small>
    <h1>Termina la configuracion inicial</h1>
    <p>Antes de conectar tus cajas, deja listo el nombre del negocio, la store principal y el branding base del cloud.</p>
</section>

@if ($errors->any())
    <section class="notice danger">{{ $errors->first() }}</section>
@endif

<section class="card">
    <div class="toolbar">
        <div>
            <small class="eyebrow">Paso unico</small>
            <h3>Negocio y store principal</h3>
        </div>
        <span class="pill">Tenant nuevo</span>
    </div>

    <form method="POST" action="{{ route('onboarding.store') }}" class="grid grid-2">
        @csrf
        <div class="field" style="grid-column: 1 / -1;">
            <label for="tenant_name">Nombre del negocio</label>
            <input id="tenant_name" name="tenant_name" value="{{ old('tenant_name', $tenant->name) }}" required>
        </div>
        <div class="field">
            <label for="business_name">Nombre comercial</label>
            <input id="business_name" name="business_name" value="{{ old('business_name', data_get($branding, 'business_name', $tenant->name)) }}" required>
        </div>
        <div class="field">
            <label for="store_name">Nombre de store</label>
            <input id="store_name" name="store_name" value="{{ old('store_name', $store->name) }}" required>
        </div>
        <div class="field">
            <label for="terminal_name">Nombre de caja por default</label>
            <input id="terminal_name" name="terminal_name" value="{{ old('terminal_name', data_get($branding, 'terminal_name', $store->name)) }}" required>
        </div>
        <div class="field">
            <label for="timezone">Timezone</label>
            <input id="timezone" name="timezone" value="{{ old('timezone', $store->timezone ?: 'America/Tijuana') }}" required>
        </div>
        <div class="field">
            <label for="owner_name">Tu nombre</label>
            <input id="owner_name" name="owner_name" value="{{ old('owner_name', $user->name) }}" required>
        </div>
        <div class="row-actions" style="grid-column: 1 / -1; justify-content: flex-end;">
            <button class="button" type="submit">Entrar a BRS Cloud</button>
        </div>
    </form>
</section>
@endsection
