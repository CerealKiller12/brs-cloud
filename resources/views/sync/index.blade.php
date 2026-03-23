@extends('layouts.app', ['title' => 'Sync | BRS Cloud'])

@section('content')
<section class="hero">
    <small>Sync</small>
    <h2>Monitor de sincronizacion</h2>
    <p>Revisa que eventos llegan desde las cajas offline-first, desde que device se originaron y que tipo de operacion estan reportando.</p>
</section>

<section class="metrics-grid">
    <article class="stat">
        <div class="stat-label">Eventos</div>
        <div class="stat-value">{{ $syncStats['total'] }}</div>
        <div class="stat-note">Registros recibidos por el tenant</div>
    </article>
    <article class="stat">
        <div class="stat-label">Devices</div>
        <div class="stat-value">{{ $syncStats['devices'] }}</div>
        <div class="stat-note">Dispositivos reportando eventos</div>
    </article>
    <article class="stat">
        <div class="stat-label">Ultimas 24h</div>
        <div class="stat-value">{{ $syncStats['last24h'] }}</div>
        <div class="stat-note">Eventos nuevos en la ultima jornada</div>
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
                <label for="device_id">Device</label>
                <select id="device_id" name="device_id">
                    <option value="">Todos los devices</option>
                    @foreach ($deviceOptions as $option)
                        <option value="{{ $option->device_id }}" {{ $deviceFilter === $option->device_id ? 'selected' : '' }}>{{ $option->name ?: $option->device_id }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="event_type">Tipo de evento</label>
                <input id="event_type" name="event_type" value="{{ $eventFilter }}" placeholder="sale.created, cash.adjusted...">
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
                        <p>Actividad recurrente en el outbox de cajas</p>
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
                <th>Aggregate</th>
                <th>Device</th>
                <th>Ocurrio</th>
                <th>Recibido</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($events as $event)
                @php($payload = json_decode($event->payload_json, true) ?: [])
                <tr>
                    <td>
                        <strong>{{ $event->event_type }}</strong><br>
                        <span class="muted">{{ $event->event_id }}</span>
                    </td>
                    <td>
                        {{ $event->aggregate_type }}<br>
                        <span class="muted">{{ \Illuminate\Support\Str::limit(json_encode($payload), 72) }}</span>
                    </td>
                    <td>{{ $event->device_id }}</td>
                    <td>{{ \Carbon\Carbon::parse($event->occurred_at)->format('M j, Y · g:i A') }}</td>
                    <td>{{ \Carbon\Carbon::parse($event->received_at)->format('M j, Y · g:i A') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5"><div class="empty">Todavia no hay eventos para esos filtros.</div></td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="pagination">{{ $events->links() }}</div>
</section>
@endsection
