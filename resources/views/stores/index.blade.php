@extends('layouts.app', ['title' => 'Sucursales | BRS Cloud'])

@section('content')
<section class="hero">
    <small>Sucursales</small>
    <h2>Organiza tus sucursales</h2>
    <p>Administra el nombre comercial, zona horaria, catalogo y estado operativo de cada sucursal.</p>
</section>

@if (session('status'))
    <section class="notice success">{{ session('status') }}</section>
@endif

@if ($errors->any())
    <section class="notice danger">{{ $errors->first() }}</section>
@endif

<section class="metrics-grid">
    <article class="stat">
        <div class="stat-label">Sucursales</div>
        <div class="stat-value">{{ $storeStats['total'] }}</div>
        <div class="stat-note">Registradas en tu cuenta</div>
    </article>
    <article class="stat">
        <div class="stat-label">Activas</div>
        <div class="stat-value">{{ $storeStats['active'] }}</div>
        <div class="stat-note">Disponibles para check-in y sync</div>
    </article>
    <article class="stat">
        <div class="stat-label">Cajas</div>
        <div class="stat-value">{{ $storeStats['devices'] }}</div>
        <div class="stat-note">Cajas asignadas a tus sucursales</div>
    </article>
    <article class="stat">
        <div class="stat-label">Catalogo</div>
        <div class="stat-value">{{ $storeStats['catalogItems'] }}</div>
        <div class="stat-note">Productos compartidos entre sucursales</div>
    </article>
</section>

<section class="grid grid-2">
    <article class="card">
        <div class="toolbar">
            <div>
                <small class="eyebrow">Editor</small>
                <h3>{{ $editStore ? 'Editar sucursal' : 'Nueva sucursal' }}</h3>
            </div>
            @if ($editStore)
                <a class="pill" href="{{ route('stores.index') }}">Cancelar edicion</a>
            @endif
        </div>

        <form method="POST" action="{{ $editStore ? route('stores.update', $editStore->id) : route('stores.store') }}" class="grid grid-2">
            @csrf
            @if ($editStore)
                @method('PUT')
            @endif

            <div class="field">
                <label for="name">Nombre de la sucursal</label>
                <input id="name" name="name" value="{{ old('name', $editStore->name ?? '') }}" required>
            </div>
            <div class="field">
                <label for="code">Codigo corto</label>
                <input id="code" name="code" value="{{ old('code', $editStore->code ?? '') }}" required>
            </div>
            <div class="field">
                <label for="timezone">Zona horaria</label>
                <input id="timezone" name="timezone" value="{{ old('timezone', $editStore->timezone ?? 'America/Tijuana') }}" required>
            </div>
            <div class="field">
                <label for="business_name">Nombre comercial</label>
                <input id="business_name" name="business_name" value="{{ old('business_name', $editStore ? data_get(is_array($editStore->branding_json) ? $editStore->branding_json : (json_decode($editStore->branding_json ?? '[]', true) ?: []), 'business_name') : '') }}">
            </div>
            <div class="field" style="grid-column: 1 / -1;">
                <label for="terminal_name">Nombre de caja por default</label>
                <input id="terminal_name" name="terminal_name" value="{{ old('terminal_name', $editStore ? data_get(is_array($editStore->branding_json) ? $editStore->branding_json : (json_decode($editStore->branding_json ?? '[]', true) ?: []), 'terminal_name') : '') }}">
            </div>
            <div class="surface" style="grid-column: 1 / -1; display: flex; align-items: center; justify-content: space-between; gap: 12px;">
                <div>
                    <h4>Estado de la sucursal</h4>
                    <p>Desactiva una sucursal si quieres detener nuevas conexiones sin borrar su historial.</p>
                </div>
                <label style="margin: 0; display: flex; align-items: center; gap: 10px;">
                    <input id="is_active" name="is_active" type="checkbox" value="1" {{ old('is_active', $editStore->is_active ?? true) ? 'checked' : '' }} style="width: auto;">
                    <span>Sucursal activa</span>
                </label>
            </div>
            <div class="row-actions" style="grid-column: 1 / -1;">
                <button class="button" type="submit">{{ $editStore ? 'Guardar sucursal' : 'Crear sucursal' }}</button>
            </div>
        </form>
    </article>

    <article class="card">
        <div class="toolbar">
            <div>
                <small class="eyebrow">Resumen</small>
                <h3>Sucursales de tu negocio</h3>
            </div>
            <span class="pill">{{ $stores->count() }} registradas</span>
        </div>

        <div class="stack">
            @foreach ($stores as $store)
                @php($branding = is_array($store->branding_json) ? $store->branding_json : (json_decode($store->branding_json ?? '[]', true) ?: []))
                <div class="surface">
                    <div class="toolbar" style="margin-bottom: 12px; align-items: flex-start;">
                        <div>
                            <h4>{{ $store->name }}</h4>
                            <p>{{ data_get($branding, 'business_name', $store->name) }} · {{ $store->timezone }}</p>
                            <p class="muted">Referencia {{ $store->code }}</p>
                        </div>
                        <span class="pill {{ $store->is_active ? 'success' : 'danger' }}">{{ $store->is_active ? 'Activa' : 'Inactiva' }}</span>
                    </div>
                    <div class="grid grid-2" style="gap: 12px; margin-bottom: 14px;">
                        <div>
                            <small class="eyebrow">Experiencia</small>
                            <p>{{ data_get($branding, 'business_name', 'Sin nombre comercial') }}</p>
                            <p class="muted">Caja sugerida: {{ data_get($branding, 'terminal_name', 'Sin nombre base') }}</p>
                        </div>
                        <div>
                            <small class="eyebrow">Operacion</small>
                            <p>{{ $deviceCounts[$store->id] ?? 0 }} cajas · {{ $catalogCounts[$store->id] ?? 0 }} productos</p>
                            <p class="muted">Catalogo version {{ $store->catalog_version }}</p>
                        </div>
                    </div>
                    <div class="row-actions">
                        <a class="button-secondary" href="{{ route('stores.index', ['edit' => $store->id]) }}">Editar</a>
                        <form method="POST" action="{{ route('stores.rotate-key', $store->id) }}">
                            @csrf
                            <button class="button-secondary" type="submit">Renovar acceso de cajas</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    </article>
</section>
@endsection
