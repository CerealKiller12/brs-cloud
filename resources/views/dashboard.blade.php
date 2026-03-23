@extends('layouts.app', ['title' => 'Inicio | BRS Cloud'])

@push('head')
<style>
    .dash-hero {
        display: grid;
        grid-template-columns: minmax(0, 1.35fr) minmax(320px, .9fr);
        gap: 18px;
        align-items: stretch;
    }
    .dash-hero-main {
        position: relative;
        overflow: hidden;
        background:
            radial-gradient(circle at top right, rgba(223, 193, 158, .32), transparent 34%),
            linear-gradient(135deg, #182736 0%, #21384d 44%, #33526e 100%);
        color: #f7fbff;
        border-radius: 28px;
        padding: 32px;
    }
    .dash-hero-main p,
    .dash-hero-main .muted {
        color: rgba(238, 245, 251, .82);
    }
    .dash-hero-main .eyebrow {
        color: #f0cda6;
    }
    .dash-hero-main h1 {
        font-size: 42px;
        line-height: 1;
        margin-bottom: 10px;
    }
    .dash-hero-main::after {
        content: "";
        position: absolute;
        inset: auto -40px -52px auto;
        width: 180px;
        height: 180px;
        border-radius: 999px;
        background: rgba(255, 255, 255, .08);
        filter: blur(2px);
    }
    .dash-hero-side {
        display: grid;
        gap: 16px;
    }
    .hero-mini {
        display: grid;
        gap: 10px;
        align-content: start;
    }
    .hero-mini .stat-value {
        margin-bottom: 0;
    }
    .hero-badges {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 18px;
    }
    .hero-badges .pill {
        background: rgba(255,255,255,.08);
        color: #f0f6fb;
        border-color: rgba(255,255,255,.12);
    }
    .dash-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 22px;
    }
    .dash-actions .button-secondary {
        background: rgba(255,255,255,.08);
        color: #fff;
        border-color: rgba(255,255,255,.14);
    }
    .dash-grid {
        display: grid;
        gap: 18px;
    }
    .dash-grid-2 {
        grid-template-columns: 1.2fr .8fr;
    }
    .dash-grid-3 {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 16px;
    }
    .kpi-card {
        padding: 18px;
        border-radius: 22px;
        border: 1px solid var(--line);
        background: linear-gradient(180deg, rgba(255,255,255,.98) 0%, rgba(246,250,253,.98) 100%);
    }
    .kpi-card .stat-value {
        font-size: 34px;
        margin-bottom: 4px;
    }
    .chart-card {
        display: grid;
        gap: 18px;
    }
    .chart-wrap {
        position: relative;
        min-height: 300px;
    }
    .chart-wrap.small {
        min-height: 250px;
    }
    .mini-metrics {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
    }
    .mini-metric {
        padding: 14px 16px;
        border-radius: 18px;
        background: var(--panel-soft);
        border: 1px solid var(--line);
    }
    .mini-metric strong {
        display: block;
        font-size: 22px;
        margin-bottom: 4px;
    }
    .task-list {
        display: grid;
        gap: 12px;
    }
    .task-item {
        display: grid;
        gap: 10px;
        padding: 16px 18px;
        border-radius: 18px;
        border: 1px solid var(--line);
        background: var(--panel-soft);
    }
    .task-item.done {
        background: #eef7ef;
        border-color: #c9e6cf;
    }
    .task-head {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        align-items: start;
    }
    .task-title {
        font-size: 17px;
        font-weight: 700;
        color: var(--text);
    }
    .task-status {
        font-size: 12px;
        letter-spacing: .12em;
        text-transform: uppercase;
        color: #678198;
    }
    .task-item.done .task-status {
        color: #2f6d48;
    }
    .alert-list,
    .rank-list {
        display: grid;
        gap: 12px;
    }
    .alert-row,
    .rank-row {
        display: flex;
        justify-content: space-between;
        gap: 14px;
        align-items: center;
        padding: 14px 16px;
        border-radius: 18px;
        background: var(--panel-soft);
        border: 1px solid var(--line);
    }
    .alert-row strong,
    .rank-row strong {
        display: block;
        margin-bottom: 4px;
    }
    .metric-chip {
        min-width: 52px;
        text-align: center;
        padding: 8px 10px;
        border-radius: 999px;
        background: #edf3f8;
        border: 1px solid var(--line);
        color: #3d566d;
        font-weight: 700;
    }
    .feed-list {
        display: grid;
        gap: 12px;
    }
    .feed-row {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        padding: 14px 0;
        border-bottom: 1px solid var(--line);
    }
    .feed-row:last-child {
        border-bottom: 0;
        padding-bottom: 0;
    }
    .feed-time {
        color: var(--muted);
        white-space: nowrap;
    }
    .section-title {
        display: flex;
        align-items: end;
        justify-content: space-between;
        gap: 14px;
        margin-bottom: 18px;
    }
    .section-title p {
        max-width: 60ch;
    }
    @media (max-width: 1280px) {
        .dash-hero,
        .dash-grid-2,
        .kpi-grid,
        .dash-grid-3,
        .mini-metrics {
            grid-template-columns: 1fr 1fr;
        }
    }
    @media (max-width: 820px) {
        .dash-hero,
        .dash-grid-2,
        .kpi-grid,
        .dash-grid-3,
        .mini-metrics {
            grid-template-columns: 1fr;
        }
        .dash-hero-main h1 {
            font-size: 34px;
        }
        .task-head,
        .feed-row,
        .alert-row,
        .rank-row {
            flex-direction: column;
            align-items: start;
        }
        .feed-time {
            white-space: normal;
        }
    }
</style>
@endpush

@section('content')
@if (session('status'))
<section class="notice success">{{ session('status') }}</section>
@endif

<section class="dash-hero">
    <article class="dash-hero-main">
        <small class="eyebrow">Centro de operacion</small>
        <h1>{{ $store->name }}</h1>
        <p>{{ data_get(is_array($store->branding_json ?? null) ? $store->branding_json : (json_decode($store->branding_json ?? '[]', true) ?: []), 'business_name', $tenant->name ?? 'Tu negocio') }}</p>

        <div class="hero-badges">
            <span class="pill">Catalogo version {{ $store->catalog_version ?? 0 }}</span>
            <span class="pill">{{ $stats['onlineDevices'] }} caja(s) activas</span>
            <span class="pill">{{ $stats['salesToday'] }} venta(s) hoy</span>
        </div>

        <div class="dash-actions">
            <a class="button" href="{{ route('catalog.index') }}">Abrir catalogo</a>
            <a class="button-secondary" href="{{ route('devices.index') }}">Ver cajas</a>
            <a class="button-secondary" href="{{ route('sync.index') }}">Revisar actividad</a>
        </div>
    </article>

    <div class="dash-hero-side">
        <article class="card hero-mini">
            <small class="eyebrow">Pulso de hoy</small>
            <div class="stat-value">{{ $stats['salesToday'] }}</div>
            <p>ventas registradas en esta sucursal hoy</p>
        </article>
        <article class="card hero-mini">
            <small class="eyebrow">Atencion requerida</small>
            <div class="stat-value">{{ $stats['conflicts'] + $stats['lowStock'] }}</div>
            <p>{{ $stats['conflicts'] }} incidencia(s) de sincronizacion y {{ $stats['lowStock'] }} producto(s) con stock bajo.</p>
        </article>
    </div>
</section>

<section class="kpi-grid">
    <article class="kpi-card">
        <div class="stat-label">Ventas hoy</div>
        <div class="stat-value">{{ $stats['salesToday'] }}</div>
        <div class="stat-note">tickets confirmados por tus cajas</div>
    </article>
    <article class="kpi-card">
        <div class="stat-label">Cajas activas</div>
        <div class="stat-value">{{ $stats['onlineDevices'] }}</div>
        <div class="stat-note">reportando en los ultimos 10 minutos</div>
    </article>
    <article class="kpi-card">
        <div class="stat-label">Catalogo</div>
        <div class="stat-value">{{ $stats['catalogItems'] }}</div>
        <div class="stat-note">productos compartidos disponibles</div>
    </article>
    <article class="kpi-card">
        <div class="stat-label">Stock bajo</div>
        <div class="stat-value">{{ $stats['lowStock'] }}</div>
        <div class="stat-note">productos que requieren atencion</div>
    </article>
    <article class="kpi-card">
        <div class="stat-label">Incidencias</div>
        <div class="stat-value">{{ $stats['conflicts'] }}</div>
        <div class="stat-note">movimientos frenados o con error</div>
    </article>
</section>

<section class="dash-grid dash-grid-2">
    <article class="card chart-card">
        <div class="section-title">
            <div>
                <small class="eyebrow">Actividad semanal</small>
                <h3>Como se esta moviendo la sucursal</h3>
            </div>
            <p>Compara la actividad total enviada por tus cajas contra las ventas registradas de los ultimos 7 dias.</p>
        </div>

        <div class="chart-wrap">
            <canvas id="activityTimelineChart"></canvas>
        </div>

        <div class="mini-metrics">
            <div class="mini-metric">
                <span class="stat-label">Movimientos 7 dias</span>
                <strong>{{ collect($activityTimeline)->sum('events') }}</strong>
                <span class="muted">todo lo sincronizado recientemente</span>
            </div>
            <div class="mini-metric">
                <span class="stat-label">Ventas 7 dias</span>
                <strong>{{ collect($activityTimeline)->sum('sales') }}</strong>
                <span class="muted">tickets enviados desde cajas</span>
            </div>
            <div class="mini-metric">
                <span class="stat-label">Sucursales activas</span>
                <strong>{{ $stats['stores'] }}</strong>
                <span class="muted">espacios operando dentro de tu cuenta</span>
            </div>
        </div>
    </article>

    <article class="card">
        <div class="section-title">
            <div>
                <small class="eyebrow">Checklist</small>
                <h3>Lo importante para dejarlo listo</h3>
            </div>
            <p>Este panel te dice rapido si ya resolviste lo minimo para operar sin friccion.</p>
        </div>

        <div class="task-list">
            @foreach ($nextSteps as $step)
                <div class="task-item {{ $step['done'] ? 'done' : '' }}">
                    <div class="task-head">
                        <div>
                            <div class="task-title">{{ $step['title'] }}</div>
                            <p>{{ $step['detail'] }}</p>
                        </div>
                        <span class="task-status">{{ $step['done'] ? 'Completo' : 'Pendiente' }}</span>
                    </div>
                    <div>
                        <a class="{{ $step['done'] ? 'button-secondary' : 'button' }}" href="{{ $step['cta'] }}">{{ $step['ctaLabel'] }}</a>
                    </div>
                </div>
            @endforeach
        </div>
    </article>
</section>

<section class="dash-grid dash-grid-2">
    <article class="card chart-card">
        <div class="section-title">
            <div>
                <small class="eyebrow">Mezcla operativa</small>
                <h3>Que esta pasando en la nube</h3>
            </div>
            <p>Un vistazo a los movimientos mas frecuentes de los ultimos 7 dias para entender si tu operacion esta cargada a ventas, inventario o altas de catalogo.</p>
        </div>

        <div class="chart-wrap small">
            <canvas id="eventMixChart"></canvas>
        </div>

        <div class="alert-list">
            @forelse ($topEventMix as $row)
                <div class="alert-row">
                    <div>
                        <strong>{{ $row->label }}</strong>
                        <p>Movimiento reciente dentro del flujo compartido de cajas y catalogo.</p>
                    </div>
                    <span class="metric-chip">{{ $row->aggregate }}</span>
                </div>
            @empty
                <div class="empty">Todavia no hay suficiente movimiento para construir esta mezcla.</div>
            @endforelse
        </div>
    </article>

    <article class="card">
        <div class="section-title">
            <div>
                <small class="eyebrow">Rendimiento de cajas</small>
                <h3>Quienes estan moviendo mas actividad</h3>
            </div>
            <p>Te ayuda a ubicar rapido la caja con mas movimiento o detectar si alguna se quedo fria.</p>
        </div>

        <div class="rank-list">
            @forelse ($deviceActivity as $device)
                <div class="rank-row">
                    <div>
                        <strong>{{ $device->label }}</strong>
                        <p>actividad registrada en los ultimos 7 dias</p>
                    </div>
                    <span class="metric-chip">{{ $device->aggregate }}</span>
                </div>
            @empty
                <div class="empty">Todavia no hay cajas con suficiente historial para comparar.</div>
            @endforelse
        </div>
    </article>
</section>

<section class="dash-grid dash-grid-2">
    <article class="card">
        <div class="section-title">
            <div>
                <small class="eyebrow">Inventario</small>
                <h3>Productos que necesitan atencion</h3>
            </div>
            <a class="pill" href="{{ route('catalog.index') }}">Abrir catalogo</a>
        </div>

        <div class="alert-list">
            @forelse ($lowStockProducts as $product)
                <div class="alert-row">
                    <div>
                        <strong>{{ $product->name }}</strong>
                        <p>{{ $product->sku ? 'SKU '.$product->sku.' · ' : '' }}punto de reorden {{ $product->reorder_point }}</p>
                    </div>
                    <span class="metric-chip">{{ $product->stock_on_hand }}</span>
                </div>
            @empty
                <div class="empty">Muy bien: no hay productos con stock bajo en esta sucursal.</div>
            @endforelse
        </div>
    </article>

    <article class="card">
        <div class="section-title">
            <div>
                <small class="eyebrow">Actividad reciente</small>
                <h3>Lo ultimo que ocurrio</h3>
            </div>
            <a class="pill" href="{{ route('sync.index') }}">Ver detalle</a>
        </div>

        <div class="feed-list">
            @forelse ($recentEvents as $event)
                <div class="feed-row">
                    <div>
                        <strong>{{ $event->event_label }}</strong>
                        <p>{{ $event->device_label }} · {{ $event->detail_label }}</p>
                    </div>
                    <div class="feed-time">{{ \Carbon\Carbon::parse($event->received_at)->format('M j, Y · g:i A') }}</div>
                </div>
            @empty
                <div class="empty">Aun no hay actividad reciente que mostrar en esta sucursal.</div>
            @endforelse
        </div>
    </article>
</section>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
(() => {
    if (typeof Chart === 'undefined') {
        return;
    }

    const timeline = @json($activityTimeline);
    const eventMix = @json($topEventMix->map(fn ($row) => ['label' => $row->label, 'value' => (int) $row->aggregate])->values());

    const timelineCanvas = document.getElementById('activityTimelineChart');
    if (timelineCanvas) {
        new Chart(timelineCanvas, {
            type: 'bar',
            data: {
                labels: timeline.map((point) => point.label),
                datasets: [
                    {
                        type: 'bar',
                        label: 'Movimientos',
                        data: timeline.map((point) => point.events),
                        backgroundColor: '#d6e4f0',
                        borderRadius: 12,
                        borderSkipped: false,
                    },
                    {
                        type: 'line',
                        label: 'Ventas',
                        data: timeline.map((point) => point.sales),
                        borderColor: '#1f3244',
                        backgroundColor: 'rgba(31, 50, 68, .14)',
                        borderWidth: 3,
                        tension: .35,
                        fill: false,
                        pointBackgroundColor: '#1f3244',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                    }
                ]
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            boxWidth: 10,
                            color: '#486175',
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#6a7a8f' }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(216, 224, 232, .65)' },
                        ticks: { color: '#6a7a8f', precision: 0 }
                    }
                }
            }
        });
    }

    const mixCanvas = document.getElementById('eventMixChart');
    if (mixCanvas && eventMix.length) {
        new Chart(mixCanvas, {
            type: 'doughnut',
            data: {
                labels: eventMix.map((row) => row.label),
                datasets: [{
                    data: eventMix.map((row) => row.value),
                    backgroundColor: ['#1f3244', '#4e7598', '#8fb4cf', '#d4b48d', '#8bbf9f'],
                    borderWidth: 0,
                    hoverOffset: 8,
                }]
            },
            options: {
                maintainAspectRatio: false,
                cutout: '64%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            boxWidth: 10,
                            color: '#486175',
                        }
                    }
                }
            }
        });
    }
})();
</script>
@endpush
