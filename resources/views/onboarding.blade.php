@extends('layouts.app', ['title' => ($needsBusinessSetup ? 'Crea tu negocio' : 'Configuracion inicial').' | Venpi Cloud'])

@php
    $tenantNameValue = old('tenant_name', $tenant?->name ?? '');
    $storeNameValue = old('store_name', $store?->name ?? 'Sucursal principal');
    $terminalNameValue = old('terminal_name', data_get($branding ?? [], 'terminal_name', 'Caja principal'));
    $timezoneValue = old('timezone', $store?->timezone ?? 'America/Tijuana');
    $ownerNameValue = old('owner_name', $user->name ?? '');
@endphp

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
            <small>{{ $needsBusinessSetup ? 'Bienvenido a Venpi Cloud' : 'Configuracion inicial' }}</small>
            <h1>{{ $needsBusinessSetup ? 'Crea tu negocio antes de entrar a Venpi Cloud' : 'Termina de ajustar tu negocio antes de conectar cajas' }}</h1>
            <p>
                {{ $needsBusinessSetup
                    ? 'Tu cuenta ya quedo autenticada. Ahora define el negocio base, la sucursal principal y el nombre sugerido de la primera caja.'
                    : 'Este paso deja lista la identidad del negocio, la sucursal principal y la presentacion base que heredaran tus primeras cajas.' }}
            </p>

            <div class="setup-points">
                <div class="setup-point">
                    <strong>{{ $needsBusinessSetup ? 'Negocio listo para operar' : 'Negocio alineado desde el inicio' }}</strong>
                    <span class="muted">Define el nombre con el que se vera tu cuenta en Venpi Cloud y en las cajas conectadas.</span>
                </div>
                <div class="setup-point">
                    <strong>Sucursal principal preparada</strong>
                    <span class="muted">Sera la base para compartir catalogo, actividad y sincronizacion entre cajas.</span>
                </div>
                <div class="setup-point">
                    <strong>Caja sugerida desde el primer arranque</strong>
                    <span class="muted">El nombre inicial de la caja se hereda al conectar el primer dispositivo y luego lo puedes cambiar.</span>
                </div>
            </div>
        </section>

        <aside class="card setup-side">
            <div class="toolbar" style="margin-bottom: 0;">
                <div>
                    <small class="eyebrow">Resumen</small>
                    <h3>{{ $needsBusinessSetup ? 'Lo que se va a crear' : 'Lo que se va a confirmar' }}</h3>
                </div>
                <span class="pill">{{ $needsBusinessSetup ? 'Alta inicial' : 'Ultimo paso' }}</span>
            </div>

            <div class="setup-overview">
                <div class="setup-overview-card">
                    <span class="stat-label">Cuenta</span>
                    <strong>{{ $user->name ?: 'Responsable principal' }}</strong>
                    <span class="muted">{{ $user->email ?: 'Sin correo' }}</span>
                </div>
                <div class="setup-overview-card">
                    <span class="stat-label">Resultado</span>
                    <strong>{{ $needsBusinessSetup ? 'Negocio + sucursal' : ($store?->name ?? 'Sucursal principal') }}</strong>
                    <span class="muted">{{ $needsBusinessSetup ? 'Se creara la base operativa para conectar cajas.' : 'Se cerrara la configuracion inicial de tu cuenta.' }}</span>
                </div>
            </div>

            <div class="surface">
                <h4>Despues de este paso</h4>
                <p>Podras ajustar marca, tema, catalogo, dispositivos y accesos sin volver a pasar por este formulario.</p>
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
                <h3>{{ $needsBusinessSetup ? 'Crear negocio y sucursal principal' : 'Confirmar datos base del negocio' }}</h3>
            </div>
            <span class="pill">{{ $needsBusinessSetup ? 'Primer negocio' : 'Configuracion inicial' }}</span>
        </div>

        <form method="POST" action="{{ route('onboarding.store') }}" class="grid grid-2">
            @csrf
            <div class="field" style="grid-column: 1 / -1;">
                <label for="tenant_name">Nombre del negocio</label>
                <input id="tenant_name" name="tenant_name" value="{{ $tenantNameValue }}" required>
            </div>
            <div class="field">
                <label for="store_name">Sucursal principal</label>
                <input id="store_name" name="store_name" value="{{ $storeNameValue }}" required>
            </div>
            <div class="field">
                <label for="terminal_name">Nombre sugerido para nuevas cajas</label>
                <input id="terminal_name" name="terminal_name" value="{{ $terminalNameValue }}" required>
            </div>
            <div class="field">
                <label for="timezone">Zona horaria</label>
                <input id="timezone" name="timezone" value="{{ $timezoneValue }}" required>
            </div>
            <div class="field">
                <label for="owner_name">Tu nombre</label>
                <input id="owner_name" name="owner_name" value="{{ $ownerNameValue }}" required>
            </div>
            <div class="row-actions" style="grid-column: 1 / -1; justify-content: flex-end;">
                <button class="button" type="submit">{{ $needsBusinessSetup ? 'Crear negocio y entrar' : 'Entrar a Venpi Cloud' }}</button>
            </div>
        </form>
    </section>
</section>
@endsection