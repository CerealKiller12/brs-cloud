@extends('layouts.app', ['title' => 'Cajas | BRS Cloud'])

@push('head')
<style>
    .devices-shell {
        display: grid;
        gap: 18px;
    }
    .devices-top {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 18px;
    }
    .device-filter-card {
        background:
            radial-gradient(circle at top right, rgba(223, 193, 158, .18), transparent 34%),
            linear-gradient(180deg, rgba(255,255,255,.99) 0%, rgba(246,250,253,.99) 100%);
    }
    .device-highlights {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }
    .device-highlight {
        padding: 14px 16px;
        border-radius: 18px;
        background: var(--panel-soft);
        border: 1px solid var(--line);
    }
    .device-highlight strong {
        display: block;
        font-size: 24px;
        margin-bottom: 4px;
    }
    .device-list {
        display: grid;
        gap: 14px;
    }
    .device-card {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 18px;
        padding: 20px;
        border-radius: 24px;
        background: linear-gradient(180deg, rgba(255,255,255,.99) 0%, rgba(246,250,253,.99) 100%);
        border: 1px solid var(--line);
        box-shadow: 0 16px 40px rgba(30,55,90,.05);
    }
    .device-card-main {
        display: grid;
        gap: 14px;
    }
    .device-card-head {
        display: flex;
        justify-content: space-between;
        gap: 14px;
        align-items: start;
    }
    .device-card-meta {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        align-items: center;
    }
    .device-health {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
    }
    .device-health-item {
        padding: 14px 16px;
        border-radius: 18px;
        background: var(--panel-soft);
        border: 1px solid var(--line);
    }
    .device-health-item strong {
        display: block;
        font-size: 22px;
        margin-bottom: 4px;
    }
    .device-side {
        min-width: 220px;
        display: grid;
        gap: 12px;
        align-content: start;
    }
    .device-stamp {
        color: var(--muted);
        font-size: 14px;
        text-align: right;
    }
    @media (max-width: 1180px) {
        .devices-top,
        .device-highlights,
        .device-health {
            grid-template-columns: 1fr;
        }
    }
    @media (max-width: 820px) {
        .device-card {
            grid-template-columns: 1fr;
        }
        .device-card-head {
            flex-direction: column;
        }
        .device-stamp {
            text-align: left;
        }
    }
</style>
@endpush

@section('content')
<section class="hero">
    <small>Cajas conectadas</small>
    <h2>La salud de tus cajas, sin ruido tecnico</h2>
    <p>Revisa rapido cuales cajas estan activas, cuales se enfriaron y cuales necesitan una accion para volver a quedar al dia.</p>
</section>

@if (session('status'))
    <section class="notice success">{{ session('status') }}</section>
@endif

<section class="metrics-grid">
    <article class="stat">
        <div class="stat-label">Cajas</div>
        <div class="stat-value">{{ $deviceStats['total'] }}</div>
        <div class="stat-note">registradas en tu cuenta</div>
    </article>
    <article class="stat">
        <div class="stat-label">iOS</div>
        <div class="stat-value">{{ $deviceStats['ios'] }}</div>
        <div class="stat-note">iPads y iPhones enlazados</div>
    </article>
    <article class="stat">
        <div class="stat-label">Escritorio</div>
        <div class="stat-value">{{ $deviceStats['desktop'] }}</div>
        <div class="stat-note">puntos de venta en desktop</div>
    </article>
    <article class="stat">
        <div class="stat-label">Ultimas 24 horas</div>
        <div class="stat-value">{{ $deviceStats['seenToday'] }}</div>
        <div class="stat-note">con actividad reciente</div>
    </article>
    <article class="stat">
        <div class="stat-label">Atrasadas</div>
        <div class="stat-value">{{ $deviceStats['stale'] }}</div>
        <div class="stat-note">sin reportar desde hace mas de 24 horas</div>
    </article>
    <article class="stat">
        <div class="stat-label">Con incidencias</div>
        <div class="stat-value">{{ $deviceStats['withConflicts'] }}</div>
        <div class="stat-note">con movimientos frenados en la nube</div>
    </article>
</section>

<section class="devices-shell">
    <div class="devices-top">
        <article class="card device-filter-card">
            <div class="toolbar">
                <div>
                    <small class="eyebrow">Buscador</small>
                    <h3>Encuentra la caja que quieres revisar</h3>
                    <p>Filtra por nombre, plataforma o sucursal para centrarte solo en las cajas que te interesa revisar hoy.</p>
                </div>
            </div>
            <form method="GET" action="{{ route('devices.index') }}" class="grid grid-2">
                <div class="field" style="grid-column: 1 / -1;">
                    <label for="q">Buscar caja</label>
                    <input id="q" name="q" value="{{ $search }}" placeholder="Nombre de caja, plataforma o modo">
                </div>
                <div class="field">
                    <label for="store_id">Sucursal</label>
                    <select id="store_id" name="store_id">
                        <option value="">Todas las sucursales</option>
                        @foreach ($storeOptions as $storeOption)
                            <option value="{{ $storeOption->id }}" {{ (string) $storeFilter === (string) $storeOption->id ? 'selected' : '' }}>{{ $storeOption->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="row-actions" style="grid-column: 1 / -1;">
                    <button class="button-secondary" type="submit">Aplicar filtros</button>
                </div>
            </form>
        </article>

        <article class="card">
            <div class="toolbar">
                <div>
                    <small class="eyebrow">Resumen rapido</small>
                    <h3>Como va tu red de cajas</h3>
                </div>
            </div>
            <div class="device-highlights">
                <div class="device-highlight">
                    <span class="stat-label">Conectadas ahora</span>
                    <strong>{{ max($deviceStats['seenToday'] - $deviceStats['stale'], 0) }}</strong>
                    <span class="muted">cajas con actividad util reciente</span>
                </div>
                <div class="device-highlight">
                    <span class="stat-label">Atencion requerida</span>
                    <strong>{{ $deviceStats['stale'] + $deviceStats['withConflicts'] }}</strong>
                    <span class="muted">cajas atrasadas o con incidencias</span>
                </div>
                <div class="device-highlight">
                    <span class="stat-label">Entorno iOS</span>
                    <strong>{{ $deviceStats['ios'] }}</strong>
                    <span class="muted">operando en iPad / iPhone</span>
                </div>
                <div class="device-highlight">
                    <span class="stat-label">Entorno escritorio</span>
                    <strong>{{ $deviceStats['desktop'] }}</strong>
                    <span class="muted">operando en cajas desktop</span>
                </div>
            </div>
        </article>
    </div>

    <section class="card">
        <div class="toolbar">
            <div>
                <small class="eyebrow">Listado</small>
                <h3>Tus cajas conectadas</h3>
                <p>Te muestro el estado de cada caja, cuanta actividad ha tenido y si sigue teniendo acceso a sincronizar.</p>
            </div>
        </div>

        <div class="device-list">
            @forelse ($devices as $device)
                @php($health = $syncHealthByDevice[$device->device_id] ?? null)
                @php($isOnline = $device->last_seen_at && \Carbon\Carbon::parse($device->last_seen_at)->gte(now()->subMinutes(10)))
                @php($isRecent = $device->last_seen_at && \Carbon\Carbon::parse($device->last_seen_at)->gte(now()->subDay()))
                @php($healthClass = $isOnline ? 'success' : ($isRecent ? '' : 'danger'))
                @php($healthLabel = $isOnline ? 'En linea' : ($isRecent ? 'Reciente' : 'Atrasada'))
                @php($deviceLabel = $device->name ?: ($device->platform === 'ios' ? 'Caja iPad' : ($device->platform === 'desktop' ? 'Caja de escritorio' : 'Caja conectada')))
                <article class="device-card">
                    <div class="device-card-main">
                        <div class="device-card-head">
                            <div>
                                <strong style="font-size: 22px;">{{ $deviceLabel }}</strong>
                                <div class="device-card-meta">
                                    <span class="pill {{ $healthClass }}">{{ $healthLabel }}</span>
                                    <span class="pill">{{ $storeNames[$device->store_id] ?? 'Sin sucursal' }}</span>
                                    <span class="pill">{{ $device->platform === 'ios' ? 'iPad / iPhone' : ($device->platform === 'desktop' ? 'Escritorio' : ($device->platform ?: 'Sin definir')) }}</span>
                                    <span class="pill">{{ $device->current_version ?: 'Version no disponible' }}</span>
                                </div>
                            </div>
                            <span class="pill">{{ ($tokenCounts[$device->id] ?? 0) > 0 ? 'Acceso activo' : 'Sin acceso activo' }}</span>
                        </div>

                        <div class="device-health">
                            <div class="device-health-item">
                                <span class="stat-label">Movimientos</span>
                                <strong>{{ $health->total_events ?? 0 }}</strong>
                                <span class="muted">recibidos de esta caja</span>
                            </div>
                            <div class="device-health-item">
                                <span class="stat-label">Aplicados</span>
                                <strong>{{ $health->applied_events ?? 0 }}</strong>
                                <span class="muted">ya reflejados por cloud</span>
                            </div>
                            <div class="device-health-item">
                                <span class="stat-label">Incidencias</span>
                                <strong>{{ $health->conflict_events ?? 0 }}</strong>
                                <span class="muted">requieren revisarse</span>
                            </div>
                        </div>
                    </div>

                    <div class="device-side">
                        <div class="device-stamp">
                            <strong style="display:block; color: var(--text); margin-bottom: 4px;">Ultima actividad</strong>
                            {{ optional($device->last_seen_at)->format('M j, Y · g:i A') ?: 'Sin check-in reciente' }}
                        </div>

                        @if (!empty($health?->last_event_at))
                            <div class="device-stamp">
                                <strong style="display:block; color: var(--text); margin-bottom: 4px;">Ultimo movimiento recibido</strong>
                                {{ \Carbon\Carbon::parse($health->last_event_at)->format('M j, Y · g:i A') }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('devices.revoke-token', $device->id) }}" onsubmit="return confirm('Se revocaran todos los tokens de esta caja.');">
                            @csrf
                            <button class="button-danger" type="submit">Desvincular acceso</button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="empty">No hay cajas que coincidan con ese filtro.</div>
            @endforelse
        </div>

        <div class="pagination">{{ $devices->links() }}</div>
    </section>
</section>
@endsection
