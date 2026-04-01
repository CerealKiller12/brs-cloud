@extends('layouts.app', ['title' => 'Inicio de sucursal | Venpi Cloud'])

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
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: 16px;
    }
    .kpi-card {
        padding: 18px;
        border-radius: 22px;
        border: 1px solid var(--line);
        background: linear-gradient(180deg, rgba(255,255,255,.98) 0%, rgba(246,250,253,.98) 100%);
    }
    .dash-hero > *,
    .dash-hero-side > *,
    .dash-grid-2 > *,
    .dash-grid-3 > *,
    .kpi-grid > *,
    .mini-metrics > * {
        min-width: 0;
    }
    .kpi-card .stat-value {
        font-size: 34px;
        margin-bottom: 4px;
    }
    .chart-card {
        display: grid;
        gap: 18px;
        min-width: 0;
    }
    .chart-wrap {
        position: relative;
        min-height: 300px;
        min-width: 0;
        overflow: hidden;
    }
    .chart-wrap.small {
        min-height: 250px;
    }
    .chart-wrap.compact {
        min-height: 220px;
    }
    .chart-wrap canvas {
        display: block;
        max-width: 100%;
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
    .metric-chip.money {
        min-width: 92px;
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
    .money-value {
        font-variant-numeric: tabular-nums;
    }
    @media (max-width: 1220px) {
        .dash-grid-2 {
            grid-template-columns: 1fr;
        }
        .chart-wrap {
            min-height: 260px;
        }
        .chart-wrap.small,
        .chart-wrap.compact {
            min-height: 220px;
        }
    }
    @media (max-width: 1280px) {
        .dash-hero,
        .kpi-grid,
        .dash-grid-3,
        .mini-metrics {
            grid-template-columns: 1fr 1fr;
        }
    }
    @media (max-width: 680px) {
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
        <small class="eyebrow">Sucursal activa</small>
        <h1>{{ $store->name }}</h1>
        <p>{{ data_get(is_array($store->branding_json ?? null) ? $store->branding_json : (json_decode($store->branding_json ?? '[]', true) ?: []), 'business_name', $tenant->name ?? 'Tu negocio') }}</p>

        <div class="hero-badges">
            <span class="pill">Version {{ $store->catalog_version ?? 0 }}</span>
            <span class="pill">{{ $stats['onlineDevices'] }} caja(s) activas</span>
            <span class="pill">MX${{ number_format($stats['salesTodayAmountCents'] / 100, 2) }} hoy</span>
        </div>

        <div class="dash-actions">
            <a class="button" href="{{ route('catalog.index') }}">Abrir catalogo</a>
            <a class="button-secondary" href="{{ route('devices.index') }}">Ver cajas</a>
            <a class="button-secondary" href="{{ route('sync.index') }}">Revisar actividad</a>
        </div>
    </article>

    <div class="dash-hero-side">
        <article class="card hero-mini">
            <small class="eyebrow">Ingreso de hoy</small>
            <div class="stat-value money-value">MX${{ number_format($stats['salesTodayAmountCents'] / 100, 2) }}</div>
            <p>{{ $stats['salesToday'] }} ticket(s) registrados hoy en esta sucursal.</p>
        </article>
        <article class="card hero-mini">
            <small class="eyebrow">Comparativo rapido</small>
            <div class="stat-value">
                @if (!is_null($stats['salesDeltaPercent']))
                    {{ $stats['salesDeltaPercent'] > 0 ? '+' : '' }}{{ $stats['salesDeltaPercent'] }}%
                @else
                    --
                @endif
            </div>
            <p>
                @if (!is_null($stats['salesDeltaPercent']))
                    frente a ayer en ingreso vendido.
                @elseif ($stats['salesTodayAmountCents'] > 0)
                    Hoy ya hay ventas, pero ayer no hubo base para comparar.
                @else
                    Aun no hay ventas hoy para comparar contra ayer.
                @endif
            </p>
        </article>
    </div>
</section>

<section class="kpi-grid">
    <article class="kpi-card">
        <div class="stat-label">Ingreso hoy</div>
        <div class="stat-value money-value">MX${{ number_format($stats['salesTodayAmountCents'] / 100, 2) }}</div>
        <div class="stat-note">importe cobrado por tus cajas hoy</div>
    </article>
    <article class="kpi-card">
        <div class="stat-label">Tickets hoy</div>
        <div class="stat-value">{{ $stats['salesToday'] }}</div>
        <div class="stat-note">ventas registradas en esta sucursal</div>
    </article>
    <article class="kpi-card">
        <div class="stat-label">Ticket promedio</div>
        <div class="stat-value money-value">MX${{ number_format($stats['averageTicketTodayCents'] / 100, 2) }}</div>
        <div class="stat-note">promedio de cobro por ticket hoy</div>
    </article>
    <article class="kpi-card">
        <div class="stat-label">Ingreso 7 dias</div>
        <div class="stat-value money-value">MX${{ number_format($stats['salesLast7DaysAmountCents'] / 100, 2) }}</div>
        <div class="stat-note">{{ $stats['salesLast7Days'] }} ticket(s) en la ultima semana</div>
    </article>
    <article class="kpi-card">
        <div class="stat-label">Cajas activas</div>
        <div class="stat-value">{{ $stats['onlineDevices'] }}</div>
        <div class="stat-note">reportando en los ultimos 10 minutos</div>
    </article>
    <article class="kpi-card">
        <div class="stat-label">Atencion requerida</div>
        <div class="stat-value">{{ $stats['conflicts'] + $stats['lowStock'] }}</div>
        <div class="stat-note">{{ $stats['conflicts'] }} incidencia(s) y {{ $stats['lowStock'] }} producto(s) con stock bajo</div>
    </article>
</section>

<section class="dash-grid dash-grid-2">
    <article class="card chart-card">
        <div class="section-title">
            <div>
                <small class="eyebrow">Ventas de la semana</small>
                <h3>Ingreso y tickets en los ultimos 7 dias</h3>
            </div>
            <p>Te muestra si la sucursal esta vendiendo mas, si hay dias flojos y si el ritmo de tickets acompana al ingreso que entra.</p>
        </div>

        <div class="chart-wrap">
            <canvas id="salesTimelineChart"></canvas>
        </div>

        <div class="mini-metrics">
            <div class="mini-metric">
                <span class="stat-label">Ingreso semanal</span>
                <strong class="money-value">MX${{ number_format($stats['salesLast7DaysAmountCents'] / 100, 2) }}</strong>
                <span class="muted">dinero cobrado en los ultimos 7 dias</span>
            </div>
            <div class="mini-metric">
                <span class="stat-label">Promedio semanal</span>
                <strong class="money-value">MX${{ number_format($stats['averageTicket7DaysCents'] / 100, 2) }}</strong>
                <span class="muted">ticket promedio de la ultima semana</span>
            </div>
            <div class="mini-metric">
                <span class="stat-label">Comparativo</span>
                <strong>
                    @if (!is_null($stats['salesDeltaPercent']))
                        {{ $stats['salesDeltaPercent'] > 0 ? '+' : '' }}{{ $stats['salesDeltaPercent'] }}%
                    @else
                        --
                    @endif
                </strong>
                <span class="muted">variacion de hoy frente al dia anterior</span>
            </div>
        </div>
    </article>

    <article class="card">
        <div class="section-title">
            <div>
                <small class="eyebrow">Pendientes clave</small>
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
                <small class="eyebrow">Cobro</small>
                <h3>Como te estan pagando</h3>
            </div>
            <p>Esta mezcla te ayuda a ver si dependes mas del efectivo, si la tarjeta va creciendo o si hay mucho ticket mixto.</p>
        </div>

        <div class="chart-wrap small">
            <canvas id="paymentMixChart"></canvas>
        </div>

        <div class="alert-list">
            @forelse ($paymentMix as $row)
                <div class="alert-row">
                    <div>
                        <strong>{{ $row->label }}</strong>
                        <p>{{ $row->tickets }} ticket(s) en los ultimos 7 dias.</p>
                    </div>
                    <span class="metric-chip money">MX${{ number_format($row->amountCents / 100, 2) }}</span>
                </div>
            @empty
                <div class="empty">Todavia no hay ventas suficientes para construir esta mezcla de cobro.</div>
            @endforelse
        </div>
    </article>

    <article class="card">
        <div class="section-title">
            <div>
                <small class="eyebrow">Productos mas movidos</small>
                <h3>Que se esta yendo mas rapido</h3>
            </div>
            <p>Sirve para anticipar reabasto y detectar productos que ya se volvieron importantes en la sucursal.</p>
        </div>

        <div class="rank-list">
            @forelse ($topProducts as $product)
                <div class="rank-row">
                    <div>
                        <strong>{{ $product->name }}</strong>
                        <p>{{ $product->sku ? 'SKU '.$product->sku.' · ' : '' }}{{ $product->tickets }} ticket(s) en la ultima semana</p>
                    </div>
                    <span class="metric-chip">{{ $product->quantity }}</span>
                </div>
            @empty
                <div class="empty">Todavia no hay suficientes ventas para detectar productos destacados.</div>
            @endforelse
        </div>
    </article>
</section>

<section class="dash-grid dash-grid-2">
    <article class="card chart-card">
        <div class="section-title">
            <div>
                <small class="eyebrow">Horas fuertes</small>
                <h3 id="hourlySalesHeading">En que momentos vende mas la sucursal hoy</h3>
            </div>
            <p id="hourlySalesSummary">
                {{ $hourlySalesModes['today']['summary'] ?? 'En cuanto entren ventas hoy, aqui veras las horas donde se concentra el movimiento.' }}
            </p>
        </div>

        <div class="toolbar" style="margin-top: -6px; margin-bottom: 6px;">
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <button type="button" class="pill hourly-toggle is-active" data-mode="today">Hoy</button>
                <button type="button" class="pill hourly-toggle" data-mode="weeklyAverage">Promedio semanal</button>
            </div>
        </div>

        <div class="chart-wrap compact">
            <canvas id="hourlySalesChart"></canvas>
        </div>
    </article>

    <article class="card">
        <div class="section-title">
            <div>
                <small class="eyebrow">Rendimiento por caja</small>
                <h3>Quien esta generando mas ventas</h3>
            </div>
            <a class="pill" href="{{ route('devices.index') }}">Ver cajas</a>
        </div>

        <div class="rank-list">
            @forelse ($deviceSales as $device)
                <div class="rank-row">
                    <div>
                        <strong>{{ $device->label }}</strong>
                        <p>{{ $device->tickets }} ticket(s) · promedio MX${{ number_format($device->averageTicketCents / 100, 2) }}</p>
                    </div>
                    <span class="metric-chip money">MX${{ number_format($device->amountCents / 100, 2) }}</span>
                </div>
            @empty
                <div class="empty">Todavia no hay ventas suficientes para comparar cajas.</div>
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
                    <div class="feed-time">{{ $event->displayed_at?->format('M j, Y · g:i A') ?? 'Sin horario' }}</div>
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

    const money = new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN',
        maximumFractionDigits: 0,
    });

    const salesTimeline = @json($salesTimeline);
    const paymentMix = @json($paymentMix->map(fn ($row) => ['label' => $row->label, 'tickets' => (int) $row->tickets, 'amountCents' => (int) $row->amountCents])->values());
    const hourlySalesModes = @json($hourlySalesModes);

    const salesTimelineCanvas = document.getElementById('salesTimelineChart');
    if (salesTimelineCanvas) {
        new Chart(salesTimelineCanvas, {
            type: 'bar',
            data: {
                labels: salesTimeline.map((point) => point.label),
                datasets: [
                    {
                        type: 'bar',
                        label: 'Ingreso',
                        data: salesTimeline.map((point) => Math.round(point.amountCents / 100)),
                        backgroundColor: '#d6e4f0',
                        borderRadius: 12,
                        borderSkipped: false,
                        yAxisID: 'y',
                    },
                    {
                        type: 'line',
                        label: 'Tickets',
                        data: salesTimeline.map((point) => point.tickets),
                        borderColor: '#1f3244',
                        backgroundColor: 'rgba(31, 50, 68, .14)',
                        borderWidth: 3,
                        tension: .35,
                        fill: false,
                        pointBackgroundColor: '#1f3244',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        yAxisID: 'y1',
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
                        ticks: {
                            color: '#6a7a8f',
                            callback: (value) => money.format(Number(value)),
                        }
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        grid: { display: false },
                        ticks: { color: '#6a7a8f', precision: 0 }
                    }
                }
            }
        });
    }

    const paymentMixCanvas = document.getElementById('paymentMixChart');
    if (paymentMixCanvas && paymentMix.length) {
        new Chart(paymentMixCanvas, {
            type: 'doughnut',
            data: {
                labels: paymentMix.map((row) => row.label),
                datasets: [{
                    data: paymentMix.map((row) => Math.round(row.amountCents / 100)),
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
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) => `${context.label}: ${money.format(context.raw)}`
                        }
                    }
                }
            }
        });
    }

    const hourlySalesCanvas = document.getElementById('hourlySalesChart');
    if (hourlySalesCanvas) {
        const hourlyButtons = Array.from(document.querySelectorAll('.hourly-toggle'));
        const hourlyHeading = document.getElementById('hourlySalesHeading');
        const hourlySummary = document.getElementById('hourlySalesSummary');
        let hourlyMode = 'today';
        const hourlyChart = new Chart(hourlySalesCanvas, {
            type: 'bar',
            data: {
                labels: hourlySalesModes[hourlyMode].points.map((point) => point.label),
                datasets: [
                    {
                        label: 'Tickets',
                        data: hourlySalesModes[hourlyMode].points.map((point) => point.tickets),
                        backgroundColor: '#4e7598',
                        borderRadius: 10,
                        borderSkipped: false,
                    },
                    {
                        type: 'line',
                        label: 'Ingreso',
                        data: hourlySalesModes[hourlyMode].points.map((point) => Math.round(point.amountCents / 100)),
                        borderColor: '#d4b48d',
                        backgroundColor: 'rgba(212, 180, 141, .18)',
                        borderWidth: 3,
                        tension: .35,
                        pointRadius: 3,
                        yAxisID: 'y1',
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
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) => {
                                if (context.dataset.label === 'Tickets') {
                                    const value = Number(context.raw);
                                    return `Tickets: ${hourlyMode === 'weeklyAverage' ? value.toFixed(1) : value}`;
                                }

                                return `Ingreso: ${money.format(Number(context.raw))}`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            color: '#6a7a8f',
                            callback: (_, index) => index % 2 === 0 ? (hourlySalesModes[hourlyMode].points[index]?.label ?? '') : '',
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(216, 224, 232, .65)' },
                        ticks: {
                            color: '#6a7a8f',
                            callback: (value) => hourlyMode === 'weeklyAverage' ? Number(value).toFixed(1) : Number(value).toFixed(0),
                        }
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        grid: { display: false },
                        ticks: {
                            color: '#6a7a8f',
                            callback: (value) => money.format(Number(value)),
                        }
                    }
                }
            }
        });

        const applyHourlyMode = (mode) => {
            hourlyMode = mode;
            const state = hourlySalesModes[mode];
            hourlyChart.data.labels = state.points.map((point) => point.label);
            hourlyChart.data.datasets[0].data = state.points.map((point) => point.tickets);
            hourlyChart.data.datasets[1].data = state.points.map((point) => Math.round(point.amountCents / 100));
            hourlyChart.update();

            if (hourlyHeading) {
                hourlyHeading.textContent = state.heading;
            }
            if (hourlySummary) {
                hourlySummary.textContent = state.summary;
            }
            hourlyButtons.forEach((button) => {
                button.classList.toggle('is-active', button.dataset.mode === mode);
            });
        };

        hourlyButtons.forEach((button) => {
            button.addEventListener('click', () => {
                if (button.dataset.mode && hourlySalesModes[button.dataset.mode]) {
                    applyHourlyMode(button.dataset.mode);
                }
            });
        });
    }
})();
</script>
@endpush
