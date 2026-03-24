@extends('layouts.admin', ['title' => 'Clientes | BRS Admin'])

@section('content')
<section class="hero">
    <small>Clientes</small>
    <h2>Negocios dados de alta en BRS Cloud</h2>
    <p>Consulta tenants, responsables, plan, salud operativa y abre la ficha de cada cliente para administrarlo.</p>
</section>

<section class="card">
    <div class="toolbar">
        <div>
            <small class="eyebrow">Explorador</small>
            <h3>Filtra tus clientes</h3>
            <p class="muted">Busca por nombre del negocio, slug, correo principal o plan.</p>
        </div>
        <form method="GET" action="{{ route('admin.clients.index') }}" class="toolbar-stack">
            <input name="q" value="{{ $filters['q'] }}" placeholder="Buscar negocio o correo" style="min-width: 280px;">
            <select name="status">
                <option value="">Todos los estados</option>
                @foreach ($statusOptions as $status)
                    <option value="{{ $status }}" {{ $filters['status'] === $status ? 'selected' : '' }}>{{ $status }}</option>
                @endforeach
            </select>
            <select name="plan">
                <option value="">Todos los planes</option>
                @foreach ($planOptions as $plan)
                    <option value="{{ $plan }}" {{ $filters['plan'] === $plan ? 'selected' : '' }}>{{ $plan }}</option>
                @endforeach
            </select>
            <button class="button-secondary" type="submit">Aplicar</button>
        </form>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Responsable</th>
                <th>Plan</th>
                <th>Operacion</th>
                <th>Ultima actividad</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($clients as $client)
                <tr>
                    <td>
                        <strong>{{ $client->name }}</strong>
                        <div class="muted">{{ $client->slug }}</div>
                        <div style="margin-top: 6px;">
                            <span class="pill {{ $client->status_pill }}">{{ $client->subscription_status }}</span>
                            <span class="pill {{ $client->is_active ? 'success' : 'danger' }}">{{ $client->is_active ? 'Activo' : 'Suspendido' }}</span>
                        </div>
                    </td>
                    <td>
                        <strong>{{ $client->owner_name ?: 'Sin responsable' }}</strong>
                        <div class="muted">{{ $client->owner_email ?: 'Sin correo principal' }}</div>
                    </td>
                    <td>
                        <strong>{{ strtoupper($client->plan_code) }}</strong>
                        <div class="muted">Trial: {{ $client->trial_ends_at?->format('M j, Y') ?: 'No aplica' }}</div>
                    </td>
                    <td>
                        <div class="muted">{{ $client->stores_count }} sucursal(es)</div>
                        <div class="muted">{{ $client->devices_count }} caja(s)</div>
                        <div class="muted">{{ $client->users_count }} usuario(s)</div>
                    </td>
                    <td>
                        <strong>{{ $client->last_event_at ? \Carbon\Carbon::parse($client->last_event_at)->format('M j, Y · g:i A') : 'Sin actividad' }}</strong>
                        <div class="muted">{{ $client->sync_events_count }} eventos sync</div>
                    </td>
                    <td style="text-align:right;">
                        <a class="button-secondary" href="{{ route('admin.clients.show', $client->id) }}">Administrar</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">
                        <div class="surface">
                            <strong>No encontramos clientes con ese filtro.</strong>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="pagination">{{ $clients->links() }}</div>
</section>
@endsection
