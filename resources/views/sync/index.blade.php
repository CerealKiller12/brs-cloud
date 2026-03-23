@extends('layouts.app', ['title' => 'Actividad | BRS Cloud'])

@push('head')
<style>
    .activity-shell {
        display: grid;
        gap: 18px;
    }
    .activity-top {
        display: grid;
        grid-template-columns: 1.15fr .85fr;
        gap: 18px;
    }
    .activity-filter-card {
        background:
            radial-gradient(circle at top right, rgba(223, 193, 158, .18), transparent 36%),
            linear-gradient(180deg, rgba(255,255,255,.98) 0%, rgba(246,250,253,.98) 100%);
    }
    .activity-summary {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }
    .summary-chip {
        padding: 14px 16px;
        border-radius: 18px;
        background: var(--panel-soft);
        border: 1px solid var(--line);
    }
    .summary-chip strong {
        display: block;
        font-size: 24px;
        margin-bottom: 4px;
    }
    .activity-feed {
        display: grid;
        gap: 14px;
    }
    .activity-card {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 16px;
        padding: 18px 20px;
        border-radius: 22px;
        background: linear-gradient(180deg, rgba(255,255,255,.99) 0%, rgba(246,250,253,.99) 100%);
        border: 1px solid var(--line);
        box-shadow: 0 14px 40px rgba(30,55,90,.05);
    }
    .activity-main {
        display: grid;
        gap: 12px;
    }
    .activity-headline {
        display: flex;
        gap: 12px;
        justify-content: space-between;
        align-items: start;
    }
    .activity-meta {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        align-items: center;
    }
    .activity-detail {
        padding: 14px 16px;
        border-radius: 18px;
        background: var(--panel-soft);
        border: 1px solid var(--line);
    }
    .activity-side {
        min-width: 220px;
        display: grid;
        gap: 12px;
        align-content: start;
    }
    .activity-stamp {
        text-align: right;
        color: var(--muted);
        font-size: 14px;
    }
    .timeline-note {
        padding-left: 16px;
        border-left: 3px solid #d6e4f0;
    }
    .trend-card {
        display: grid;
        gap: 12px;
    }
    .trend-row {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: center;
        padding: 14px 16px;
        border-radius: 18px;
        background: var(--panel-soft);
        border: 1px solid var(--line);
    }
    .trend-row strong {
        display: block;
        margin-bottom: 4px;
    }
    .trend-count {
        min-width: 48px;
        text-align: center;
        padding: 8px 10px;
        border-radius: 999px;
        background: #edf3f8;
        border: 1px solid var(--line);
        color: #3d566d;
        font-weight: 700;
    }
    @media (max-width: 1180px) {
        .activity-top {
            grid-template-columns: 1fr;
        }
    }
    @media (max-width: 820px) {
        .activity-summary {
            grid-template-columns: 1fr;
        }
        .activity-card {
            grid-template-columns: 1fr;
        }
        .activity-stamp {
            text-align: left;
        }
        .activity-headline {
            flex-direction: column;
        }
    }
</style>
@endpush

@section('content')
<section class="hero">
    <small>Actividad</small>
    <h2>Todo lo que se esta moviendo en tu operacion</h2>
    <p>Desde aqui puedes ver lo que envian tus cajas, detectar si algo se atoró y entender rapidamente que parte del negocio se esta moviendo mas.</p>
</section>

<section class="metrics-grid">
    <article class="stat">
        <div class="stat-label">Movimientos</div>
        <div class="stat-value">{{ $syncStats['total'] }}</div>
        <div class="stat-note">eventos recibidos para esta sucursal</div>
    </article>
    <article class="stat">
        <div class="stat-label">Aplicados</div>
        <div class="stat-value">{{ $syncStats['applied'] }}</div>
        <div class="stat-note">ya reflejados en catalogo o inventario</div>
    </article>
    <article class="stat">
        <div class="stat-label">Incidencias</div>
        <div class="stat-value">{{ $syncStats['conflicts'] }}</div>
        <div class="stat-note">requieren revision o una accion tuya</div>
    </article>
    <article class="stat">
        <div class="stat-label">Ultimo movimiento</div>
        <div class="stat-value" style="font-size: 18px; line-height: 1.25;">{{ $syncStats['lastEventAt'] ? \Carbon\Carbon::parse($syncStats['lastEventAt'])->format('M j, Y · g:i A') : 'Sin eventos' }}</div>
        <div class="stat-note">lo mas reciente que entro a la nube</div>
    </article>
</section>

<section class="activity-shell">
    <div class="activity-top">
        <article class="card activity-filter-card">
            <div class="toolbar">
                <div>
                    <small class="eyebrow">Explorador</small>
                    <h3>Filtra la actividad que quieres revisar</h3>
                    <p>Puedes enfocarte por sucursal, caja o por el tipo de movimiento que te interesa inspeccionar.</p>
                </div>
            </div>

            <form method="GET" action="{{ route('sync.index') }}" class="grid grid-2">
                <div class="field">
                    <label for="store_id">Sucursal</label>
                    <select id="store_id" name="store_id">
                        @foreach ($storeOptions as $option)
                            <option value="{{ $option->id }}" {{ (int) $storeFilter === (int) $option->id ? 'selected' : '' }}>{{ $option->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="device_id">Caja</label>
                    <select id="device_id" name="device_id">
                        <option value="">Todas las cajas</option>
                        @foreach ($deviceOptions as $option)
                            <option value="{{ $option->device_id }}" {{ $deviceFilter === $option->device_id ? 'selected' : '' }}>{{ $option->display_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field" style="grid-column: 1 / -1;">
                    <label for="event_type">Tipo de movimiento</label>
                    <input id="event_type" name="event_type" value="{{ $eventFilter }}" placeholder="Venta registrada, caja abierta, producto actualizado...">
                </div>
                <div class="row-actions" style="grid-column: 1 / -1;">
                    <button class="button-secondary" type="submit">Aplicar filtros</button>
                </div>
            </form>
        </article>

        <article class="card trend-card">
            <div class="toolbar">
                <div>
                    <small class="eyebrow">Pulso rapido</small>
                    <h3>En que se esta yendo la actividad</h3>
                </div>
            </div>

            <div class="activity-summary">
                <div class="summary-chip">
                    <span class="stat-label">Cajas activas</span>
                    <strong>{{ $syncStats['devices'] }}</strong>
                    <span class="muted">en la actividad filtrada</span>
                </div>
                <div class="summary-chip">
                    <span class="stat-label">Ultimas 24h</span>
                    <strong>{{ $syncStats['last24h'] }}</strong>
                    <span class="muted">movimientos recientes</span>
                </div>
            </div>

            <div class="stack">
                @forelse ($topEventTypes as $row)
                    <div class="trend-row">
                        <div>
                            <strong>{{ $row->display_label }}</strong>
                            <p>movimiento recurrente dentro del flujo de cajas y catalogo</p>
                        </div>
                        <span class="trend-count">{{ $row->aggregate }}</span>
                    </div>
                @empty
                    <div class="empty">Todavia no hay actividad suficiente para mostrar una tendencia clara.</div>
                @endforelse
            </div>
        </article>
    </div>

    <section class="card">
        <div class="toolbar">
            <div>
                <small class="eyebrow">Linea de tiempo</small>
                <h3>Ultimos movimientos recibidos</h3>
                <p>Esta vista busca que veas rapido que paso, desde que caja vino y si se aplico o se atoró.</p>
            </div>
        </div>

        <div class="activity-feed">
            @forelse ($events as $event)
                @php($statusClass = $event->apply_error ? 'danger' : ($event->applied_at ? 'success' : ''))
                @php($statusLabel = $event->apply_error ? 'Necesita revision' : ($event->applied_at ? 'Aplicado' : 'Pendiente'))
                <article class="activity-card">
                    <div class="activity-main">
                        <div class="activity-headline">
                            <div>
                                <strong style="font-size: 20px;">{{ $event->event_label }}</strong>
                                <div class="activity-meta">
                                    <span class="pill">{{ $event->store_name }}</span>
                                    <span class="pill">{{ $event->device_label }}</span>
                                    <span class="pill">{{ $event->aggregate_label }}</span>
                                </div>
                            </div>
                            <span class="pill {{ $statusClass }}">{{ $statusLabel }}</span>
                        </div>

                        <div class="activity-detail">
                            <strong style="display:block; margin-bottom: 6px;">Resumen</strong>
                            <p>{{ $event->detail_label }}</p>
                        </div>

                        <div class="timeline-note">
                            @if ($event->apply_error)
                                <strong style="display:block; margin-bottom: 4px;">Que necesita tu atencion</strong>
                                <p>{{ \Illuminate\Support\Str::limit($event->apply_error, 180) }}</p>
                            @elseif ($event->applied_at)
                                <strong style="display:block; margin-bottom: 4px;">Resultado</strong>
                                <p>Este movimiento ya quedo reflejado en la nube el {{ \Carbon\Carbon::parse($event->applied_at)->format('M j, Y · g:i A') }}.</p>
                            @else
                                <strong style="display:block; margin-bottom: 4px;">Estado</strong>
                                <p>La nube ya lo recibio y sigue en espera de aplicarlo por completo.</p>
                            @endif
                        </div>
                    </div>

                    <div class="activity-side">
                        <div class="activity-stamp">
                            <strong style="display:block; color: var(--text); margin-bottom: 4px;">Recibido</strong>
                            {{ \Carbon\Carbon::parse($event->received_at)->format('M j, Y · g:i A') }}
                        </div>
                    </div>
                </article>
            @empty
                <div class="empty">Todavia no hay actividad para esos filtros.</div>
            @endforelse
        </div>

        <div class="pagination">{{ $events->links() }}</div>
    </section>
</section>
@endsection
