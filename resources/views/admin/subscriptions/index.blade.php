@extends('layouts.admin', ['title' => 'Subscripciones | BRS Admin'])

@section('content')
<section class="hero">
    <small>Subscripciones</small>
    <h2>Estado comercial de los clientes</h2>
    <p>Mientras no exista una tabla de billing separada, esta vista usa el estado comercial actual de cada tenant como fuente de verdad operativa.</p>
</section>

<section class="card">
    <div class="toolbar">
        <div>
            <small class="eyebrow">Filtro</small>
            <h3>Explora por plan o estado</h3>
        </div>
        <form method="GET" action="{{ route('admin.subscriptions.index') }}" class="toolbar-stack">
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
                <th>Plan</th>
                <th>Estado</th>
                <th>Trial</th>
                <th>Operacion</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($subscriptions as $tenant)
                <tr>
                    <td>
                        <strong>{{ $tenant->name }}</strong>
                        <div class="muted">{{ $tenant->owner_email ?: 'Sin correo principal' }}</div>
                    </td>
                    <td>{{ strtoupper($tenant->plan_code) }}</td>
                    <td><span class="pill {{ $tenant->status_pill }}">{{ $tenant->subscription_status }}</span></td>
                    <td>{{ $tenant->trial_ends_at?->format('M j, Y · g:i A') ?: 'Sin trial' }}</td>
                    <td><span class="pill {{ $tenant->is_active ? 'success' : 'danger' }}">{{ $tenant->is_active ? 'Activo' : 'Suspendido' }}</span></td>
                    <td style="text-align:right;">
                        <a class="button-secondary" href="{{ route('admin.clients.show', $tenant->id) }}">Administrar</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6"><div class="surface">No hay subscripciones con ese filtro.</div></td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="pagination">{{ $subscriptions->links() }}</div>
</section>
@endsection
