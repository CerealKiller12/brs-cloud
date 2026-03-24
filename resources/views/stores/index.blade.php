@extends('layouts.app', ['title' => 'Sucursales | Venpi Cloud'])

@push('head')
<style>
    .stores-shell {
        display: grid;
        gap: 18px;
    }
    .stores-top {
        display: grid;
        grid-template-columns: minmax(0, .88fr) minmax(0, 1.12fr);
        gap: 18px;
    }
    .store-editor {
        background:
            radial-gradient(circle at top right, rgba(223, 193, 158, .18), transparent 34%),
            linear-gradient(180deg, rgba(255,255,255,.99) 0%, rgba(246,250,253,.99) 100%);
    }
    .store-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
    }
    .store-card {
        display: grid;
        gap: 16px;
        padding: 20px;
        border-radius: 24px;
        background: linear-gradient(180deg, rgba(255,255,255,.99) 0%, rgba(246,250,253,.99) 100%);
        border: 1px solid var(--line);
        box-shadow: 0 16px 40px rgba(30,55,90,.05);
    }
    .store-card.active-store {
        border-color: #cddded;
        box-shadow: 0 18px 48px rgba(40,73,110,.08);
    }
    .store-head {
        display: flex;
        justify-content: space-between;
        align-items: start;
        gap: 14px;
    }
    .store-meta-line {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        align-items: center;
    }
    .store-kpis {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
    }
    .store-kpi {
        padding: 14px;
        border-radius: 18px;
        background: var(--panel-soft);
        border: 1px solid var(--line);
    }
    .store-kpi strong {
        display: block;
        font-size: 24px;
        margin-bottom: 4px;
    }
    .store-detail {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }
    .store-detail-card {
        padding: 14px 16px;
        border-radius: 18px;
        background: var(--panel-soft);
        border: 1px solid var(--line);
    }
    .store-detail-card strong {
        display: block;
        margin-bottom: 4px;
    }
    .store-hero-note {
        display: grid;
        gap: 8px;
    }
    .store-count-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        border-radius: 999px;
        background: #edf3f8;
        border: 1px solid var(--line);
        color: #486175;
        font-weight: 600;
    }
    @media (max-width: 1180px) {
        .stores-top,
        .store-grid,
        .store-kpis,
        .store-detail {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<section class="hero">
    <small>Sucursales</small>
    <h2>Organiza como opera tu negocio</h2>
    <p>Desde aqui defines tus sucursales, el nombre comercial que vera cada caja y la base con la que arrancan nuevos puntos de venta.</p>
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
        <div class="stat-note">espacios operativos registrados</div>
    </article>
    <article class="stat">
        <div class="stat-label">Activas</div>
        <div class="stat-value">{{ $storeStats['active'] }}</div>
        <div class="stat-note">listas para recibir nuevas cajas</div>
    </article>
    <article class="stat">
        <div class="stat-label">Cajas</div>
        <div class="stat-value">{{ $storeStats['devices'] }}</div>
        <div class="stat-note">asignadas entre todas tus sucursales</div>
    </article>
    <article class="stat">
        <div class="stat-label">Productos</div>
        <div class="stat-value">{{ $storeStats['catalogItems'] }}</div>
        <div class="stat-note">disponibles dentro del catalogo compartido</div>
    </article>
</section>

<section class="stores-shell">
    <div class="stores-top">
        <article class="card store-editor">
            <div class="toolbar">
                <div>
                    <small class="eyebrow">Editor</small>
                    <h3>{{ $editStore ? 'Actualiza esta sucursal' : 'Crea una nueva sucursal' }}</h3>
                    <p>{{ $editStore ? 'Ajusta branding, zona horaria y acceso para las cajas que ya operan aqui.' : 'Prepara una nueva sucursal para que pueda recibir cajas y compartir catalogo.' }}</p>
                </div>
                @if ($editStore)
                    <a class="pill" href="{{ route('stores.index') }}">Cancelar</a>
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
                    <label for="business_name">Nombre comercial</label>
                    <input id="business_name" name="business_name" value="{{ old('business_name', $editStore ? data_get(is_array($editStore->branding_json) ? $editStore->branding_json : (json_decode($editStore->branding_json ?? '[]', true) ?: []), 'business_name') : '') }}">
                </div>
                <div class="field">
                    <label for="timezone">Zona horaria</label>
                    <input id="timezone" name="timezone" value="{{ old('timezone', $editStore->timezone ?? 'America/Tijuana') }}" required>
                </div>
                <div class="field" style="grid-column: 1 / -1;">
                    <label for="terminal_name">Nombre sugerido para nuevas cajas</label>
                    <input id="terminal_name" name="terminal_name" value="{{ old('terminal_name', $editStore ? data_get(is_array($editStore->branding_json) ? $editStore->branding_json : (json_decode($editStore->branding_json ?? '[]', true) ?: []), 'terminal_name') : '') }}">
                </div>

                <div class="surface" style="grid-column: 1 / -1; display: flex; align-items: center; justify-content: space-between; gap: 12px;">
                    <div class="store-hero-note">
                        <h4>Disponibilidad</h4>
                        <p>Si desactivas una sucursal dejas intacto su historial, pero detienes nuevas conexiones de cajas.</p>
                    </div>
                    <label style="margin: 0; display: flex; align-items: center; gap: 10px;">
                        <input id="is_active" name="is_active" type="checkbox" value="1" {{ old('is_active', $editStore->is_active ?? true) ? 'checked' : '' }} style="width: auto;">
                        <span>Sucursal activa</span>
                    </label>
                </div>

                <div class="row-actions" style="grid-column: 1 / -1;">
                    <button class="button" type="submit">{{ $editStore ? 'Guardar cambios' : 'Crear sucursal' }}</button>
                </div>
            </form>
        </article>

        <article class="card">
            <div class="toolbar">
                <div>
                    <small class="eyebrow">Vista general</small>
                    <h3>Asi se reparte tu operacion</h3>
                    <p>Usa esta vista para ubicar rapido cual sucursal esta mas completa, cual va empezando y cual necesita mas cajas o productos.</p>
                </div>
                <span class="store-count-badge">{{ $stores->count() }} sucursal(es)</span>
            </div>

            <div class="store-grid">
                @forelse ($stores as $store)
                    @php($branding = is_array($store->branding_json) ? $store->branding_json : (json_decode($store->branding_json ?? '[]', true) ?: []))
                    @php($isCurrentStore = !empty($cloudActiveStore) && (int) $cloudActiveStore->id === (int) $store->id)
                    <article class="store-card {{ $isCurrentStore ? 'active-store' : '' }}">
                        <div class="store-head">
                            <div>
                                <h3>{{ $store->name }}</h3>
                                <div class="store-meta-line">
                                    <span class="pill">{{ data_get($branding, 'business_name', $store->name) }}</span>
                                    <span class="pill">{{ $store->timezone }}</span>
                                    @if ($isCurrentStore)
                                        <span class="pill success">Sucursal activa</span>
                                    @endif
                                </div>
                            </div>
                            <span class="pill {{ $store->is_active ? 'success' : 'danger' }}">{{ $store->is_active ? 'Activa' : 'Inactiva' }}</span>
                        </div>

                        <div class="store-kpis">
                            <div class="store-kpi">
                                <span class="stat-label">Cajas</span>
                                <strong>{{ $deviceCounts[$store->id] ?? 0 }}</strong>
                                <span class="muted">conectadas</span>
                            </div>
                            <div class="store-kpi">
                                <span class="stat-label">Productos</span>
                                <strong>{{ $catalogCounts[$store->id] ?? 0 }}</strong>
                                <span class="muted">en catalogo</span>
                            </div>
                            <div class="store-kpi">
                                <span class="stat-label">Version</span>
                                <strong>{{ $store->catalog_version }}</strong>
                                <span class="muted">del catalogo</span>
                            </div>
                        </div>

                        <div class="store-detail">
                            <div class="store-detail-card">
                                <small class="eyebrow">Marca visible</small>
                                <strong>{{ data_get($branding, 'business_name', 'Sin nombre comercial') }}</strong>
                                <p>Lo que vera el cliente o el operador en esta sucursal.</p>
                            </div>
                            <div class="store-detail-card">
                                <small class="eyebrow">Caja sugerida</small>
                                <strong>{{ data_get($branding, 'terminal_name', 'Sin nombre base') }}</strong>
                                <p>Nombre inicial para nuevas cajas conectadas aqui.</p>
                            </div>
                        </div>

                        <div class="row-actions">
                            <a class="button-secondary" href="{{ route('stores.index', ['edit' => $store->id]) }}">Editar</a>
                            <form method="POST" action="{{ route('stores.rotate-key', $store->id) }}">
                                @csrf
                                <button class="button-secondary" type="submit">Renovar acceso de cajas</button>
                            </form>
                        </div>
                    </article>
                @empty
                    <div class="empty">Todavia no has creado ninguna sucursal.</div>
                @endforelse
            </div>
        </article>
    </div>
</section>
@endsection
