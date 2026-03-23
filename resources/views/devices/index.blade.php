@extends('layouts.app', ['title' => 'Devices | BRS Cloud'])

@section('content')
<section class="hero">
    <small>Devices</small>
    <h2>Cajas y dispositivos</h2>
    <p>Monitorea la salud real de cada caja: ultima conexion, ultima sync, conflictos pendientes y necesidad de re-enrolar tokens.</p>
</section>

@if (session('status'))
    <section class="notice success">{{ session('status') }}</section>
@endif

<section class="metrics-grid">
    <article class="stat">
        <div class="stat-label">Devices</div>
        <div class="stat-value">{{ $deviceStats['total'] }}</div>
        <div class="stat-note">Activos e historicos para este tenant</div>
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
        <div class="stat-note">Con check-in reciente al cloud</div>
    </article>
    <article class="stat">
        <div class="stat-label">Atrasadas</div>
        <div class="stat-value">{{ $deviceStats['stale'] }}</div>
        <div class="stat-note">Sin reportar desde hace mas de 24h</div>
    </article>
    <article class="stat">
        <div class="stat-label">Con conflictos</div>
        <div class="stat-value">{{ $deviceStats['withConflicts'] }}</div>
        <div class="stat-note">Cajas con eventos frenados por cloud</div>
    </article>
</section>

<section class="card">
    <div class="toolbar">
        <div>
            <small class="eyebrow">Filtros</small>
            <h3>Buscar devices</h3>
        </div>
        <form method="GET" action="{{ route('devices.index') }}" class="toolbar-stack">
            <input name="q" value="{{ $search }}" placeholder="Device, plataforma o modo" style="min-width: 280px;">
            <select name="store_id" style="min-width: 220px;">
                <option value="">Todas las stores</option>
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
                <th>Device</th>
                <th>Salud</th>
                <th>Store</th>
                <th>Modo</th>
                <th>Version</th>
                <th>Sync</th>
                <th>Ultima conexion</th>
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
                        <strong>{{ $device->name ?: $device->device_id }}</strong><br>
                        <span class="muted">{{ $device->device_id }} · {{ strtoupper($device->platform ?: 'n/a') }}</span>
                    </td>
                    <td>
                        <span class="pill {{ $healthClass }}">{{ $healthLabel }}</span><br>
                        <span class="muted">{{ ($tokenCounts[$device->id] ?? 0) > 0 ? 'Token activo' : 'Sin token' }}</span>
                    </td>
                    <td>{{ $storeNames[$device->store_id] ?? 'Sin store' }}</td>
                    <td>{{ $device->app_mode ?: 'sin modo' }}</td>
                    <td>{{ $device->current_version ?: 'n/a' }}</td>
                    <td>
                        <strong>{{ $health->total_events ?? 0 }} eventos</strong><br>
                        <span class="muted">
                            {{ $health->applied_events ?? 0 }} aplicados · {{ $health->conflict_events ?? 0 }} conflictos
                        </span>
                        @if (!empty($health?->last_event_at))
                            <br><span class="muted">{{ \Carbon\Carbon::parse($health->last_event_at)->format('M j, Y · g:i A') }}</span>
                        @endif
                    </td>
                    <td>{{ optional($device->last_seen_at)->format('M j, Y · g:i A') ?: 'sin check-in' }}</td>
                    <td>
                        <form method="POST" action="{{ route('devices.revoke-token', $device->id) }}" onsubmit="return confirm('Se revocaran todos los tokens de este device.');">
                            @csrf
                            <button class="button-danger" type="submit">Revocar token</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8"><div class="empty">No hay devices que coincidan con ese filtro.</div></td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <div class="pagination">{{ $devices->links() }}</div>
</section>
@endsection
