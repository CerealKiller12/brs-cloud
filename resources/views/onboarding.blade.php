@extends('layouts.app', ['title' => 'Configuracion inicial | BRS Cloud'])

@push('head')
<style>
    .setup-shell {
        display: grid;
        gap: 18px;
    }
    .setup-grid {
        display: grid;
        grid-template-columns: 1.05fr .95fr;
        gap: 18px;
    }
    .setup-hero-card {
        background:
            radial-gradient(circle at top right, rgba(223, 193, 158, .18), transparent 34%),
            linear-gradient(180deg, rgba(255,255,255,.99) 0%, rgba(246,250,253,.99) 100%);
    }
    .setup-points {
        display: grid;
        gap: 12px;
        margin-top: 22px;
    }
    .setup-point {
        padding: 14px 16px;
        border-radius: 18px;
        border: 1px solid var(--line);
        background: rgba(255,255,255,.78);
    }
    .setup-point strong {
        display: block;
        margin-bottom: 4px;
    }
    .setup-side {
        display: grid;
        gap: 16px;
        align-content: start;
    }
    .setup-overview {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }
    .setup-overview-card {
        padding: 16px 18px;
        border-radius: 18px;
        border: 1px solid var(--line);
        background: var(--panel-soft);
    }
    .setup-overview-card strong {
        display: block;
        font-size: 22px;
        margin: 4px 0 6px;
    }
    @media (max-width: 1100px) {
        .setup-grid,
        .setup-overview {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<section class="setup-shell">
<section class="setup-grid">
    <section class="hero setup-hero-card">
        <small>Configuracion inicial</small>
        <h1>Deja listo tu negocio antes de conectar cajas</h1>
        <p>Este paso define el nombre visible del negocio, la sucursal principal y la presentacion base que heredaran tus primeras cajas.</p>

        <div class="setup-points">
            <div class="setup-point">
                <strong>Negocio listo para operar</strong>
                <span class="muted">Aqui dejas clara la identidad comercial con la que trabajaran tus cajas desde el primer inicio.</span>
            </div>
            <div class="setup-point">
                <strong>Sucursal principal preparada</strong>
                <span class="muted">Se crea como punto de partida para compartir catalogo, actividad y sincronizacion.</span>
            </div>
            <div class="setup-point">
                <strong>Cuenta principal definida</strong>
                <span class="muted">Tu nombre queda como referencia inicial para la administracion del negocio en la nube.</span>
            </div>
        </div>
    </section>

    <aside class="card setup-side">
        <div class="toolbar" style="margin-bottom: 0;">
            <div>
                <small class="eyebrow">Resumen</small>
                <h3>Lo que se va a crear</h3>
            </div>
            <span class="pill">Cuenta nueva</span>
        </div>

        <div class="setup-overview">
            <div class="setup-overview-card">
                <span class="stat-label">Negocio</span>
                <strong>{{ $tenant->name }}</strong>
                <span class="muted">Nombre interno con el que se organiza tu cuenta.</span>
            </div>
            <div class="setup-overview-card">
                <span class="stat-label">Sucursal principal</span>
                <strong>{{ $store->name }}</strong>
                <span class="muted">Base para conectar tus primeras cajas y compartir catalogo.</span>
            </div>
        </div>

        <div class="surface">
            <h4>Antes de continuar</h4>
            <p>Si ya tienes claro el nombre comercial y como quieres llamar a tu caja base, este paso solo lo haces una vez.</p>
        </div>
    </aside>
</section>

@if ($errors->any())
    <section class="notice danger">{{ $errors->first() }}</section>
@endif

<section class="card">
    <div class="toolbar">
        <div>
            <small class="eyebrow">Paso unico</small>
            <h3>Negocio y sucursal principal</h3>
        </div>
        <span class="pill">Primera configuracion</span>
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
            <label for="store_name">Nombre de la sucursal</label>
            <input id="store_name" name="store_name" value="{{ old('store_name', $store->name) }}" required>
        </div>
        <div class="field">
            <label for="terminal_name">Nombre sugerido para nuevas cajas</label>
            <input id="terminal_name" name="terminal_name" value="{{ old('terminal_name', data_get($branding, 'terminal_name', $store->name)) }}" required>
        </div>
        <div class="field">
            <label for="timezone">Zona horaria</label>
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
</section>
@endsection
