@extends('layouts.app', ['title' => 'Cuenta | Venpi Cloud'])

@push('head')
<style>
    .settings-shell {
        display: grid;
        gap: 18px;
    }
    .settings-top {
        display: grid;
        grid-template-columns: 1.15fr .85fr;
        gap: 18px;
    }
    .settings-hero-card {
        background:
            radial-gradient(circle at top right, rgba(223, 193, 158, .18), transparent 34%),
            linear-gradient(180deg, rgba(255,255,255,.99) 0%, rgba(246,250,253,.99) 100%);
    }
    .settings-summary {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
        margin-top: 22px;
    }
    .settings-summary-card {
        padding: 16px 18px;
        border-radius: 18px;
        border: 1px solid var(--line);
        background: rgba(255,255,255,.78);
    }
    .settings-summary-card strong {
        display: block;
        font-size: 24px;
        margin: 4px 0 6px;
    }
    .settings-plan-card {
        display: grid;
        gap: 14px;
        align-content: start;
    }
    .settings-plan-card .surface {
        display: grid;
        gap: 8px;
    }
    .settings-form-card {
        background:
            linear-gradient(180deg, rgba(255,255,255,.99) 0%, rgba(247,250,252,.98) 100%);
    }
    .settings-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px;
    }
    .settings-note-list {
        display: grid;
        gap: 10px;
    }
    .settings-note-item {
        padding: 14px 16px;
        border-radius: 18px;
        border: 1px solid var(--line);
        background: var(--panel-soft);
    }
    .settings-note-item strong {
        display: block;
        margin-bottom: 4px;
    }
    @media (max-width: 1100px) {
        .settings-top,
        .settings-summary,
        .settings-form-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<section class="settings-shell">
    <div class="settings-top">
        <section class="hero settings-hero-card">
            <small>Cuenta</small>
            <h2>Tu negocio y la base que comparten tus cajas</h2>
            <p>Desde aqui ajustas la informacion principal de tu cuenta, el nombre visible del negocio y la sucursal base con la que arrancan nuevas cajas.</p>

            <div class="settings-summary">
                <div class="settings-summary-card">
                    <span class="stat-label">Cuenta</span>
                    <strong>{{ $user->name }}</strong>
                    <span class="muted">{{ $user->email }}</span>
                </div>
                <div class="settings-summary-card">
                    <span class="stat-label">Negocio</span>
                    <strong>{{ $tenant->name }}</strong>
                    <span class="muted">{{ data_get($branding, 'business_name', $tenant->name) }}</span>
                </div>
                <div class="settings-summary-card">
                    <span class="stat-label">Sucursal base</span>
                    <strong>{{ $store->name }}</strong>
                    <span class="muted">{{ $store->code }} · {{ $store->timezone }}</span>
                </div>
            </div>
        </section>

        <article class="card settings-plan-card">
            <div class="toolbar" style="margin-bottom: 0;">
                <div>
                    <small class="eyebrow">Estado de cuenta</small>
                    <h3>Tu estado actual</h3>
                </div>
                <span class="pill">Plan {{ $tenant->plan_code }}</span>
            </div>

            <div class="surface">
                <span class="stat-label">Estado del plan</span>
                <strong style="font-size: 26px;">{{ $tenant->subscription_status }}</strong>
                <span class="muted">Tu cuenta sigue usando este estado para disponibilidad de funciones y cobro.</span>
            </div>

            <div class="settings-note-list">
                <div class="settings-note-item">
                    <strong>Periodo de prueba</strong>
                    <span class="muted">{{ $tenant->trial_ends_at?->format('M j, Y · g:i A') ?? 'No hay periodo de prueba activo.' }}</span>
                </div>
                <div class="settings-note-item">
                    <strong>Referencia de la cuenta</strong>
                    <span class="muted">{{ $tenant->slug }}</span>
                </div>
                <div class="settings-note-item">
                    <strong>Situacion de la sucursal principal</strong>
                    <span class="muted">{{ $store->is_active ? 'Activa y lista para recibir cajas nuevas.' : 'Inactiva. Reactivala antes de conectar nuevas cajas.' }}</span>
                </div>
            </div>
        </article>
    </div>

@if (session('status'))
    <section class="notice success">{{ session('status') }}</section>
@endif

@if ($errors->any())
    <section class="notice danger">{{ $errors->first() }}</section>
@endif

<section class="settings-form-grid">
    <article class="card settings-form-card">
        <div class="toolbar">
            <div>
                <small class="eyebrow">Cuenta</small>
                <h3>Datos de acceso</h3>
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
                <label for="avatar_url">Enlace de foto</label>
                <input id="avatar_url" name="avatar_url" value="{{ old('avatar_url', $user->avatar_url) }}" placeholder="https://...">
            </div>
            <div class="row-actions" style="grid-column: 1 / -1;">
                <button class="button" type="submit">Guardar cuenta</button>
            </div>
        </form>
    </article>

    <article class="card settings-form-card">
        <div class="toolbar">
            <div>
                <small class="eyebrow">Negocio</small>
                <h3>Presentacion del negocio</h3>
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
                <label for="slug">Identificador</label>
                <input id="slug" value="{{ $tenant->slug }}" disabled>
            </div>
            <div class="field">
                <label for="subscription_status">Estado del plan</label>
                <input id="subscription_status" value="{{ $tenant->subscription_status }}" disabled>
            </div>
            <div class="field">
                <label for="trial_ends_at">Periodo de prueba</label>
                <input id="trial_ends_at" value="{{ $tenant->trial_ends_at?->format('M j, Y · g:i A') ?? 'No hay periodo de prueba activo' }}" disabled>
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

<section class="card settings-form-card">
    <div class="toolbar">
        <div>
            <small class="eyebrow">Sucursal principal</small>
            <h3>Configuracion base para nuevas cajas</h3>
        </div>
        <span class="pill {{ $store->is_active ? 'success' : 'danger' }}">{{ $store->is_active ? 'Activa' : 'Inactiva' }}</span>
    </div>

    <form method="POST" action="{{ route('settings.store') }}" class="grid grid-2">
        @csrf
        <div class="field">
            <label for="store_name">Nombre de la sucursal</label>
            <input id="store_name" name="store_name" value="{{ old('store_name', $store->name) }}" required>
        </div>
        <div class="field">
            <label for="store_code">Referencia interna</label>
            <input id="store_code" value="{{ $store->code }}" disabled>
        </div>
        <div class="field">
            <label for="timezone">Zona horaria</label>
            <input id="timezone" name="timezone" value="{{ old('timezone', $store->timezone) }}" required>
        </div>
        <div class="field">
            <label for="terminal_name">Nombre sugerido para nuevas cajas</label>
            <input id="terminal_name" name="terminal_name" value="{{ old('terminal_name', data_get($branding, 'terminal_name', $store->name)) }}" required>
        </div>
        <div class="row-actions" style="grid-column: 1 / -1;">
            <button class="button" type="submit">Guardar sucursal principal</button>
        </div>
    </form>
</section>
<section class="card">
    <div class="toolbar">
        <div>
            <small class="eyebrow">Que se hereda a las cajas</small>
            <h3>Lo que ve una caja nueva al conectarse</h3>
        </div>
    </div>
    <div class="settings-summary">
        <div class="settings-summary-card">
            <span class="stat-label">Negocio visible</span>
            <strong>{{ data_get($branding, 'business_name', $tenant->name) }}</strong>
            <span class="muted">Se usa como encabezado comercial al conectar cajas nuevas.</span>
        </div>
        <div class="settings-summary-card">
            <span class="stat-label">Caja sugerida</span>
            <strong>{{ data_get($branding, 'terminal_name', $store->name) }}</strong>
            <span class="muted">Sirve como nombre inicial al instalar una caja desde cero.</span>
        </div>
        <div class="settings-summary-card">
            <span class="stat-label">Zona horaria</span>
            <strong>{{ $store->timezone }}</strong>
            <span class="muted">Se usa para fechas operativas y cortes en esta sucursal.</span>
        </div>
    </div>
</section>
</section>
@endsection
