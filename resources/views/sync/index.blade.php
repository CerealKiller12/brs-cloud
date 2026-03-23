@extends('layouts.app', ['title' => 'Sync | BRS Cloud'])

@section('content')
<section class="hero">
    <small>Sync</small>
    <h2>Monitor de sincronizacion</h2>
    <p>Revisa lo que reporta cada caja, si el evento ya se aplico al snapshot compartido y si hubo conflictos por version de catalogo.</p>
</section>

<section class="metrics-grid">
    <article class="stat">
        <div class="stat-label">Eventos</div>
        <div class="stat-value">{{ $syncStats['total'] }}</div>
        <div class="stat-note">Eventos del contexto activo</div>
    </article>
    <article class="stat">
        <div class="stat-label">Aplicados</div>
        <div class="stat-value">{{ $syncStats['applied'] }}</div>
        <div class="stat-note">Ya proyectados al catalogo cloud</div>
    </article>
    <article class="stat">
        <div class="stat-label">Conflictos</div>
        <div class="stat-value">{{ $syncStats['conflicts'] }}</div>
        <div class="stat-note">Eventos frenados por version o error</div>
    </article>
    <article class="stat">
        <div class="stat-label">Ultimo evento</div>
        <div class="stat-value" style="font-size: 18px; line-height: 1.25;">{{ $syncStats['lastEventAt'] ? \Carbon\Carbon::parse($syncStats['lastEventAt'])->format('M j, Y · g:i A') : 'Sin eventos' }}</div>
        <div class="stat-note">Marca de tiempo mas reciente</div>
    </article>
</section>

<section class="grid grid-2">
    <article class="card">
        <div class="toolbar">
            <div>
                <small class="eyebrow">Filtros</small>
                <h3>Eventos recibidos</h3>
            </div>
        </div>
        <form method="GET" action="{{ route('sync.index') }}" class="grid grid-2">
            <div class="field">
                <label for="store_id">Store</label>
                <select id="store_id" name="store_id">
                    @foreach ($storeOptions as $option)
                        <option value="{{ $option->id }}" {{ (int) $storeFilter === (int) $option->id ? 'selected' : '' }}>{{ $option->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="device_id">Device</label>
                <select id="device_id" name="device_id">
                    <option value="">Todos los devices</option>
                    @foreach ($deviceOptions as $option)
                        <option value="{{ $option->device_id }}" {{ $deviceFilter === $option->device_id ? 'selected' : '' }}>{{ $option->name ?: $option->device_id }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field" style="grid-column: 1 / -1;">
                <label for="event_type">Tipo de evento</label>
                <input id="event_type" name="event_type" value="{{ $eventFilter }}" placeholder="sale.created, cash-session.opened, product.updated...">
            </div>
            <div class="row-actions" style="grid-column: 1 / -1;">
                <button class="button-secondary" type="submit">Aplicar filtros</button>
            </div>
        </form>
    </article>

    <article class="card">
        <div class="toolbar">
            <div>
                <small class="eyebrow">Top event types</small>
                <h3>Lo que mas esta sincronizando</h3>
            </div>
        </div>
        <div class="stack">
            @forelse ($topEventTypes as $row)
                <div class="surface" style="display: flex; justify-content: space-between; align-items: center; gap: 12px;">
                    <div>
                        <strong>{{ $row->event_type }}</strong>
                        <p>Actividad recurrente del outbox de las cajas</p>
                    </div>
                    <span class="pill">{{ $row->aggregate }}</span>
                </div>
            @empty
                <div class="empty">Todavia no hay eventos para construir una distribucion.</div>
            @endforelse
        </div>
    </article>
</section>

<section class="card">
    <table class="table">
        <thead>
            <tr>
                <th>Evento</th>
                <th>Store</th>
                <th>Device</th>
                <th>Estado</th>
                <th>Payload</th>
                <th>Recibido</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($events as $event)
                @php($payload = json_decode($event->payload_json, true) ?: [])
                @php($statusClass = $event->apply_error ? 'danger' : ($event->applied_at ? 'success' : ''))
                @php($statusLabel = $event->apply_error ? 'Conflicto' : ($event->applied_at ? 'Aplicado' : 'Pendiente'))
                <tr>
                    <td>
                        <strong>{{ $event->event_type }}</strong><br>
                        <span class="muted">{{ $event->event_id }}</span>
                    </td>
                    <td>
                        <strong>{{ $event->store_id }}</strong><br>
                        <span class="muted">{{ $event->aggregate_type }}</span>
                    </td>
                    <td>{{ $event->device_id }}</td>
                    <td>
                        <span class="pill {{ $statusClass }}">{{ $statusLabel }}</span>
                        @if ($event->apply_error)
                            <p style="margin-top: 8px;">{{ \Illuminate\Support\Str::limit($event->apply_error, 110) }}</p>
                        @elseif ($event->applied_at)
                            <p style="margin-top: 8px;">{{ \Carbon\Carbon::parse($event->applied_at)->format('M j, Y · g:i A') }}</p>
                        @endif
                    </td>
                    <td>
                        <span class="muted">{{ \Illuminate\Support\Str::limit(json_encode($payload), 96) }}</span>
                    </td>
                    <td>{{ \Carbon\Carbon::parse($event->received_at)->format('M j, Y · g:i A') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6"><div class="empty">Todavia no hay eventos para esos filtros.</div></td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="pagination">{{ $events->links() }}</div>
</section>
@endsection
