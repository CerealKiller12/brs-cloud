@extends('layouts.app', ['title' => 'Cajas | BRS Cloud'])

@section('content')
<section class="hero">
    <small>Cajas conectadas</small>
    <h2>Cajas y dispositivos</h2>
    <p>Revisa que cajas estan activas, cuales necesitan atencion y cuando fue la ultima vez que reportaron actividad.</p>
</section>

@if (session('status'))
    <section class="notice success">{{ session('status') }}</section>
@endif

<section class="metrics-grid">
    <article class="stat">
        <div class="stat-label">Cajas</div>
        <div class="stat-value">{{ $deviceStats['total'] }}</div>
        <div class="stat-note">Registradas en tu cuenta</div>
    </article>
    <article class="stat">
        <div class="stat-label">iOS</div>
        <div class="stat-value">{{ $deviceStats['ios'] }}</div>
        <div class="stat-note">iPads y iPhones enrolados</div>
    </article>
    <article class="stat">
        <div class="stat-label">Desktop</div>
        <div class="stat-value">{{ $deviceStats['desktop'] }}</div>
        <div class="stat-note">POS de escritorio ligados</div>
    </article>
    <article class="stat">
        <div class="stat-label">Ultimas 24h</div>
        <div class="stat-value">{{ $deviceStats['seenToday'] }}</div>
        <div class="stat-note">Con actividad reciente en las ultimas 24 horas</div>
    </article>
    <article class="stat">
        <div class="stat-label">Requieren revision</div>
        <div class="stat-value">{{ $deviceStats['stale'] }}</div>
        <div class="stat-note">Sin reportar desde hace mas de 24 horas</div>
    </article>
    <article class="stat">
        <div class="stat-label">Con incidencias</div>
        <div class="stat-value">{{ $deviceStats['withConflicts'] }}</div>
        <div class="stat-note">Cajas con movimientos frenados por cloud</div>
    </article>
</section>

<section class="card">
    <div class="toolbar">
            <div>
                <small class="eyebrow">Filtros</small>
                <h3>Buscar cajas</h3>
            </div>
        <form method="GET" action="{{ route('devices.index') }}" class="toolbar-stack">
            <input name="q" value="{{ $search }}" placeholder="Nombre de caja, plataforma o modo" style="min-width: 280px;">
            <select name="store_id" style="min-width: 220px;">
                <option value="">Todas las sucursales</option>
                @foreach ($storeOptions as $storeOption)
                    <option value="{{ $storeOption->id }}" {{ (string) $storeFilter === (string) $storeOption->id ? 'selected' : '' }}>{{ $storeOption->name }}</option>
                @endforeach
            </select>
            <button class="button-secondary" type="submit">Filtrar</button>
        </form>
    </div>
</section>

<section class="card">
    <table class="table">
        <thead>
            <tr>
                <th>Caja</th>
                <th>Estado</th>
                <th>Sucursal</th>
                <th>Tipo</th>
                <th>Version</th>
                <th>Resumen</th>
                <th>Ultima actividad</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($devices as $device)
                @php($health = $syncHealthByDevice[$device->device_id] ?? null)
                @php($isOnline = $device->last_seen_at && \Carbon\Carbon::parse($device->last_seen_at)->gte(now()->subMinutes(10)))
                @php($isRecent = $device->last_seen_at && \Carbon\Carbon::parse($device->last_seen_at)->gte(now()->subDay()))
                @php($healthClass = $isOnline ? 'success' : ($isRecent ? '' : 'danger'))
                @php($healthLabel = $isOnline ? 'En linea' : ($isRecent ? 'Reciente' : 'Atrasada'))
                <tr>
                    <td>
                        <strong>{{ $device->name ?: ($device->platform === 'ios' ? 'Caja iPad' : ($device->platform === 'desktop' ? 'Caja de escritorio' : 'Caja conectada')) }}</strong><br>
                        <span class="muted">{{ strtoupper($device->platform ?: 'n/a') }} · {{ $device->app_mode ?: 'sin modo' }}</span>
                    </td>
                    <td>
                        <span class="pill {{ $healthClass }}">{{ $healthLabel }}</span><br>
                        <span class="muted">{{ ($tokenCounts[$device->id] ?? 0) > 0 ? 'Token activo' : 'Sin token' }}</span>
                    </td>
                    <td>{{ $storeNames[$device->store_id] ?? 'Sin sucursal' }}</td>
                    <td>{{ $device->platform === 'ios' ? 'iPad / iPhone' : ($device->platform === 'desktop' ? 'Escritorio' : ($device->platform ?: 'Sin definir')) }}</td>
                    <td>{{ $device->current_version ?: 'n/a' }}</td>
                    <td>
                        <strong>{{ $health->total_events ?? 0 }} movimientos</strong><br>
                        <span class="muted">
                            {{ $health->applied_events ?? 0 }} aplicados · {{ $health->conflict_events ?? 0 }} incidencias
                        </span>
                        @if (!empty($health?->last_event_at))
                            <br><span class="muted">{{ \Carbon\Carbon::parse($health->last_event_at)->format('M j, Y · g:i A') }}</span>
                        @endif
                    </td>
                    <td>{{ optional($device->last_seen_at)->format('M j, Y · g:i A') ?: 'sin check-in' }}</td>
                    <td>
                        <form method="POST" action="{{ route('devices.revoke-token', $device->id) }}" onsubmit="return confirm('Se revocaran todos los tokens de esta caja.');">
                            @csrf
                            <button class="button-danger" type="submit">Desvincular token</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8"><div class="empty">No hay cajas que coincidan con ese filtro.</div></td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <div class="pagination">{{ $devices->links() }}</div>
</section>
@endsection
