@extends('layouts.app', ['title' => 'Dashboard | BRS Cloud'])

@section('content')
<section class="hero">
    <small>Tenant</small>
    <h1>{{ $tenant->name ?? 'BRS Cloud' }}</h1>
    <p>
        Plan {{ $tenant->plan_code ?? 'n/a' }} · Suscripcion {{ $tenant->subscription_status ?? 'n/a' }} ·
        Store activa {{ $store->name ?? 'sin store' }}
    </p>
</section>

<section class="grid grid-4">
    <article class="stat">
        <div class="stat-label">Stores</div>
        <div class="stat-value">{{ $stats['stores'] }}</div>
        <div class="stat-note">Sucursales registradas</div>
    </article>
    <article class="stat">
        <div class="stat-label">Devices</div>
        <div class="stat-value">{{ $stats['devices'] }}</div>
        <div class="stat-note">Cajas conectadas</div>
    </article>
    <article class="stat">
        <div class="stat-label">Catalogo</div>
        <div class="stat-value">{{ $stats['catalogItems'] }}</div>
        <div class="stat-note">Productos cloud activos</div>
    </article>
    <article class="stat">
        <div class="stat-label">Sync events</div>
        <div class="stat-value">{{ $stats['pendingEvents'] }}</div>
        <div class="stat-note">Eventos recibidos</div>
    </article>
</section>

<section class="grid grid-2">
    <article class="card">
        <div class="toolbar">
            <div>
                <small class="eyebrow">Devices</small>
                <h3>Ultima actividad</h3>
            </div>
            <a class="pill" href="{{ route('devices.index') }}">Ver todos</a>
        </div>
        @if ($recentDevices->isEmpty())
            <div class="empty">Todavia no hay devices registrados en este tenant.</div>
        @else
            <div class="meta-list">
                @foreach ($recentDevices as $device)
                    <div class="meta-row">
                        <div>
                            <strong>{{ $device->name ?: $device->device_id }}</strong><br>
                            <span class="muted">{{ $device->platform }} · {{ $device->app_mode ?: 'sin modo' }}</span>
                        </div>
                        <div class="muted">{{ optional($device->last_seen_at)->format('M j, Y · g:i A') }}</div>
                    </div>
                @endforeach
            </div>
        @endif
    </article>

    <article class="card">
        <div class="toolbar">
            <div>
                <small class="eyebrow">Sync</small>
                <h3>Eventos recientes</h3>
            </div>
        </div>
        @if ($recentEvents->isEmpty())
            <div class="empty">Todavia no hay eventos sincronizados.</div>
        @else
            <div class="meta-list">
                @foreach ($recentEvents as $event)
                    <div class="meta-row">
                        <div>
                            <strong>{{ $event->event_type }}</strong><br>
                            <span class="muted">{{ $event->aggregate_type }} · {{ $event->device_id }}</span>
                        </div>
                        <div class="muted">{{ \\Illuminate\\Support\\Carbon::parse($event->received_at)->format('M j, Y · g:i A') }}</div>
                    </div>
                @endforeach
            </div>
        @endif
    </article>
</section>
@endsection
