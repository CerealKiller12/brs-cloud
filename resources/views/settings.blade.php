@extends('layouts.app', ['title' => 'Settings | BRS Cloud'])

@section('content')
<section class="hero">
    <small>Settings</small>
    <h2>Cuenta, negocio y store principal</h2>
    <p>Administra la identidad del negocio en cloud y los datos base que consumen las cajas al conectarse.</p>
</section>

@if (session('status'))
    <section class="notice success">{{ session('status') }}</section>
@endif

@if ($errors->any())
    <section class="notice danger">{{ $errors->first() }}</section>
@endif

<section class="grid grid-2">
    <article class="card">
        <div class="toolbar">
            <div>
                <small class="eyebrow">Cuenta</small>
                <h3>Owner cloud</h3>
            </div>
            <span class="pill">{{ ucfirst($user->role) }}</span>
        </div>

        <form method="POST" action="{{ route('settings.account') }}" class="grid grid-2">
            @csrf
            <div class="field" style="grid-column: 1 / -1;">
                <label for="name">Nombre</label>
                <input id="name" name="name" value="{{ old('name', $user->name) }}" required>
            </div>
            <div class="field">
                <label for="email">Correo</label>
                <input id="email" value="{{ $user->email }}" disabled>
            </div>
            <div class="field">
                <label for="avatar_url">Avatar URL</label>
                <input id="avatar_url" name="avatar_url" value="{{ old('avatar_url', $user->avatar_url) }}" placeholder="https://...">
            </div>
            <div class="row-actions" style="grid-column: 1 / -1;">
                <button class="button" type="submit">Guardar cuenta</button>
            </div>
        </form>
    </article>

    <article class="card">
        <div class="toolbar">
            <div>
                <small class="eyebrow">Tenant</small>
                <h3>Datos del negocio</h3>
            </div>
            <span class="pill">Plan {{ $tenant->plan_code }}</span>
        </div>

        <form method="POST" action="{{ route('settings.tenant') }}" class="grid grid-2">
            @csrf
            <div class="field" style="grid-column: 1 / -1;">
                <label for="tenant_name">Nombre del negocio</label>
                <input id="tenant_name" name="tenant_name" value="{{ old('tenant_name', $tenant->name) }}" required>
            </div>
            <div class="field">
                <label for="slug">Slug</label>
                <input id="slug" value="{{ $tenant->slug }}" disabled>
            </div>
            <div class="field">
                <label for="subscription_status">Suscripcion</label>
                <input id="subscription_status" value="{{ $tenant->subscription_status }}" disabled>
            </div>
            <div class="field">
                <label for="trial_ends_at">Trial</label>
                <input id="trial_ends_at" value="{{ $tenant->trial_ends_at?->format('M j, Y · g:i A') ?? 'Sin trial' }}" disabled>
            </div>
            <div class="field">
                <label for="business_name">Nombre comercial</label>
                <input id="business_name" name="business_name" value="{{ old('business_name', data_get($branding, 'business_name', $tenant->name)) }}" required>
            </div>
            <div class="row-actions" style="grid-column: 1 / -1;">
                <button class="button" type="submit">Guardar negocio</button>
            </div>
        </form>
    </article>
</section>

<section class="card">
    <div class="toolbar">
        <div>
            <small class="eyebrow">Store principal</small>
            <h3>Base operativa para tus cajas</h3>
        </div>
        <span class="pill {{ $store->is_active ? 'success' : 'danger' }}">{{ $store->is_active ? 'Activa' : 'Inactiva' }}</span>
    </div>

    <form method="POST" action="{{ route('settings.store') }}" class="grid grid-2">
        @csrf
        <div class="field">
            <label for="store_name">Nombre de store</label>
            <input id="store_name" name="store_name" value="{{ old('store_name', $store->name) }}" required>
        </div>
        <div class="field">
            <label for="store_code">Codigo</label>
            <input id="store_code" value="{{ $store->code }}" disabled>
        </div>
        <div class="field">
            <label for="timezone">Timezone</label>
            <input id="timezone" name="timezone" value="{{ old('timezone', $store->timezone) }}" required>
        </div>
        <div class="field">
            <label for="terminal_name">Nombre de caja por default</label>
            <input id="terminal_name" name="terminal_name" value="{{ old('terminal_name', data_get($branding, 'terminal_name', $store->name)) }}" required>
        </div>
        <div class="row-actions" style="grid-column: 1 / -1;">
            <button class="button" type="submit">Guardar store principal</button>
        </div>
    </form>
</section>
@endsection
