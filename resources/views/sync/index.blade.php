@extends('layouts.app', ['title' => 'Actividad | BRS Cloud'])

@section('content')
<section class="hero">
    <small>Actividad</small>
    <h2>Actividad de sincronizacion</h2>
    <p>Consulta lo que envian tus cajas, si ya quedo aplicado en el catalogo compartido y si existe algo que necesite revision.</p>
</section>

<section class="metrics-grid">
    <article class="stat">
        <div class="stat-label">Movimientos</div>
        <div class="stat-value">{{ $syncStats['total'] }}</div>
        <div class="stat-note">Recibidos en la sucursal activa</div>
    </article>
    <article class="stat">
        <div class="stat-label">Aplicados</div>
        <div class="stat-value">{{ $syncStats['applied'] }}</div>
        <div class="stat-note">Ya reflejados en el catalogo compartido</div>
    </article>
    <article class="stat">
        <div class="stat-label">Incidencias</div>
        <div class="stat-value">{{ $syncStats['conflicts'] }}</div>
        <div class="stat-note">Movimientos frenados por conflicto o error</div>
    </article>
    <article class="stat">
        <div class="stat-label">Ultimo movimiento</div>
        <div class="stat-value" style="font-size: 18px; line-height: 1.25;">{{ $syncStats['lastEventAt'] ? \Carbon\Carbon::parse($syncStats['lastEventAt'])->format('M j, Y · g:i A') : 'Sin eventos' }}</div>
        <div class="stat-note">Actividad mas reciente recibida</div>
    </article>
</section>

<section class="grid grid-2">
    <article class="card">
        <div class="toolbar">
            <div>
                <small class="eyebrow">Filtros</small>
                <h3>Filtrar actividad</h3>
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

    <article class="card">
        <div class="toolbar">
            <div>
                <small class="eyebrow">Tendencia</small>
                <h3>Lo que mas se esta moviendo</h3>
            </div>
        </div>
        <div class="stack">
            @forelse ($topEventTypes as $row)
                <div class="surface" style="display: flex; justify-content: space-between; align-items: center; gap: 12px;">
                    <div>
                        <strong>{{ $row->display_label }}</strong>
                        <p>Movimiento recurrente entre cajas y catalogo compartido</p>
                    </div>
                    <span class="pill">{{ $row->aggregate }}</span>
                </div>
            @empty
                <div class="empty">Todavia no hay actividad suficiente para mostrar una tendencia.</div>
            @endforelse
        </div>
    </article>
</section>

<section class="card">
    <table class="table">
        <thead>
            <tr>
                <th>Movimiento</th>
                <th>Sucursal</th>
                <th>Caja</th>
                <th>Resultado</th>
                <th>Resumen</th>
                <th>Recibido</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($events as $event)
                @php($statusClass = $event->apply_error ? 'danger' : ($event->applied_at ? 'success' : ''))
                @php($statusLabel = $event->apply_error ? 'Necesita revision' : ($event->applied_at ? 'Aplicado' : 'Pendiente'))
                <tr>
                    <td>
                        <strong>{{ $event->event_label }}</strong><br>
                        <span class="muted">{{ $event->aggregate_label }}</span>
                    </td>
                    <td>
                        <strong>{{ $event->store_name }}</strong>
                    </td>
                    <td>{{ $event->device_label }}</td>
                    <td>
                        <span class="pill {{ $statusClass }}">{{ $statusLabel }}</span>
                        @if ($event->apply_error)
                            <p style="margin-top: 8px;">{{ \Illuminate\Support\Str::limit($event->apply_error, 110) }}</p>
                        @elseif ($event->applied_at)
                            <p style="margin-top: 8px;">{{ \Carbon\Carbon::parse($event->applied_at)->format('M j, Y · g:i A') }}</p>
                        @endif
                    </td>
                    <td>
                        <span class="muted">{{ $event->detail_label }}</span>
                    </td>
                    <td>{{ \Carbon\Carbon::parse($event->received_at)->format('M j, Y · g:i A') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6"><div class="empty">Todavia no hay actividad para esos filtros.</div></td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="pagination">{{ $events->links() }}</div>
</section>
@endsection
