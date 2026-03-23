@extends('layouts.app', ['title' => 'Inicio | BRS Cloud'])

@section('content')
@if (session('status'))
<section class="notice success">{{ session('status') }}</section>
@endif

@if (!($tenant?->onboarding_completed_at))
<section class="card">
    <div class="toolbar">
        <div>
            <small class="eyebrow">Primeros pasos</small>
            <h3>Termina de preparar tu cuenta</h3>
            <p>Define la informacion base del negocio y deja lista tu primera sucursal antes de conectar mas cajas.</p>
        </div>
        <a class="button" href="{{ route('onboarding.index') }}">Continuar configuracion</a>
    </div>
</section>
@endif
<section class="hero">
    <small>Resumen de la sucursal activa</small>
    <h1>{{ $tenant->name ?? 'BRS Cloud' }}</h1>
    <p>
        Sucursal actual {{ $store->name ?? 'sin sucursal' }} · Plan {{ $tenant->plan_code ?? 'n/a' }} ·
        Estado {{ $tenant->subscription_status ?? 'n/a' }}
    </p>
</section>

<section class="grid grid-4">
    <article class="stat">
        <div class="stat-label">Sucursales</div>
        <div class="stat-value">{{ $stats['stores'] }}</div>
        <div class="stat-note">Puntos de venta registrados en tu cuenta</div>
    </article>
    <article class="stat">
        <div class="stat-label">Cajas en linea</div>
        <div class="stat-value">{{ $stats['onlineDevices'] }}</div>
        <div class="stat-note">Cajas activas en los ultimos 10 minutos</div>
    </article>
    <article class="stat">
        <div class="stat-label">Catalogo compartido</div>
        <div class="stat-value">{{ $stats['catalogItems'] }}</div>
        <div class="stat-note">Productos activos disponibles para tus cajas</div>
    </article>
    <article class="stat">
        <div class="stat-label">Atencion requerida</div>
        <div class="stat-value">{{ $stats['conflicts'] + $stats['lowStock'] }}</div>
        <div class="stat-note">{{ $stats['conflicts'] }} incidencias de sync · {{ $stats['lowStock'] }} productos con stock bajo</div>
    </article>
</section>

<section class="grid grid-2">
    <article class="card">
        <div class="toolbar">
            <div>
                <small class="eyebrow">Cajas</small>
                <h3>Ultima actividad por caja</h3>
            </div>
            <a class="pill" href="{{ route('devices.index') }}">Ver cajas</a>
        </div>
        @if ($recentDevices->isEmpty())
            <div class="empty">Todavia no hay cajas registradas en esta sucursal.</div>
        @else
            <div class="meta-list">
                @foreach ($recentDevices as $device)
                    <div class="meta-row">
                        <div>
                            <strong>{{ $device->name ?: $device->device_id }}</strong><br>
                            <span class="muted">{{ strtoupper($device->platform ?: 'n/a') }} · {{ $device->app_mode ?: 'sin modo' }}</span>
                        </div>
                        <div class="muted">{{ optional($device->last_seen_at)->format('M j, Y · g:i A') ?: 'Sin actividad reciente' }}</div>
                    </div>
                @endforeach
            </div>
        @endif
    </article>

    <article class="card">
        <div class="toolbar">
            <div>
                <small class="eyebrow">Actividad</small>
                <h3>Ultimos movimientos sincronizados</h3>
            </div>
            <a class="pill" href="{{ route('sync.index') }}">Ver actividad</a>
        </div>
        @if ($recentEvents->isEmpty())
            <div class="empty">Todavia no hay movimientos sincronizados en esta sucursal.</div>
        @else
            <div class="meta-list">
                @foreach ($recentEvents as $event)
                    <div class="meta-row">
                        <div>
                            @php($eventLabel = match ($event->event_type) {
                                'product.created' => 'Producto creado',
                                'product.updated' => 'Producto actualizado',
                                'product.deleted' => 'Producto eliminado',
                                'product.stock-adjusted' => 'Stock ajustado',
                                'sale.created' => 'Venta registrada',
                                default => \Illuminate\Support\Str::headline(str_replace('.', ' ', $event->event_type)),
                            })
                            <strong>{{ $eventLabel }}</strong><br>
                            <span class="muted">{{ $event->device_id }} · {{ \Illuminate\Support\Str::headline(str_replace('-', ' ', $event->aggregate_type)) }}</span>
                        </div>
                        <div class="muted">{{ \Carbon\Carbon::parse($event->received_at)->format('M j, Y · g:i A') }}</div>
                    </div>
                @endforeach
            </div>
        @endif
    </article>
</section>
@endsection
