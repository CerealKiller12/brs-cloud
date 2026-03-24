@extends('layouts.admin', ['title' => $tenant->name.' | Venpi Admin'])

@section('content')
<section class="hero">
    <small>Cliente</small>
    <h2>{{ $tenant->name }}</h2>
    <p>Administra el estado comercial y operativo del negocio desde una sola ficha.</p>
    <div class="toolbar-stack" style="margin-top: 14px;">
        <span class="pill">{{ $tenant->slug }}</span>
        <span class="pill {{ $statusPill }}">{{ $tenant->subscription_status }}</span>
        <span class="pill {{ $tenant->is_active ? 'success' : 'danger' }}">{{ $tenant->is_active ? 'Activo' : 'Suspendido' }}</span>
    </div>
</section>

<section class="metrics-grid">
    <article class="stat">
        <div class="stat-label">Sucursales</div>
        <div class="stat-value">{{ $stats['stores'] }}</div>
        <div class="stat-note">dadas de alta para este cliente</div>
    </article>
    <article class="stat">
        <div class="stat-label">Cajas</div>
        <div class="stat-value">{{ $stats['devices'] }}</div>
        <div class="stat-note">dispositivos asociados</div>
    </article>
    <article class="stat">
        <div class="stat-label">Usuarios</div>
        <div class="stat-value">{{ $stats['users'] }}</div>
        <div class="stat-note">accesos cloud registrados</div>
    </article>
    <article class="stat">
        <div class="stat-label">Actividad</div>
        <div class="stat-value">{{ number_format($stats['syncEvents']) }}</div>
        <div class="stat-note">eventos sincronizados</div>
    </article>
</section>

<section class="grid-3">
    <article class="card">
        <div class="toolbar">
            <div>
                <small class="eyebrow">Subscripcion</small>
                <h3>Plan y estado</h3>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.clients.subscription.update', $tenant->id) }}">
            @csrf
            <div class="field">
                <label for="plan_code">Plan</label>
                <select id="plan_code" name="plan_code">
                    @foreach ($planOptions as $plan)
                        <option value="{{ $plan }}" {{ $tenant->plan_code === $plan ? 'selected' : '' }}>{{ strtoupper($plan) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="subscription_status">Estado</label>
                <select id="subscription_status" name="subscription_status">
                    @foreach ($statusOptions as $status)
                        <option value="{{ $status }}" {{ $tenant->subscription_status === $status ? 'selected' : '' }}>{{ $status }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="trial_ends_at">Fin de trial</label>
                <input id="trial_ends_at" name="trial_ends_at" type="datetime-local" value="{{ $tenant->trial_ends_at?->format('Y-m-d\TH:i') }}">
            </div>
            <div class="field">
                <label for="is_active">Operacion del cliente</label>
                <select id="is_active" name="is_active">
                    <option value="1" {{ $tenant->is_active ? 'selected' : '' }}>Activa</option>
                    <option value="0" {{ ! $tenant->is_active ? 'selected' : '' }}>Suspendida</option>
                </select>
            </div>
            <button class="button" type="submit">Guardar subscripcion</button>
        </form>
    </article>

    <article class="card">
        <div class="toolbar">
            <div>
                <small class="eyebrow">Cliente</small>
                <h3>Datos base</h3>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.clients.profile.update', $tenant->id) }}">
            @csrf
            <div class="field">
                <label for="tenant_name">Nombre del negocio</label>
                <input id="tenant_name" name="tenant_name" value="{{ $tenant->name }}" required>
            </div>
            <div class="field">
                <label for="slug">Slug</label>
                <input id="slug" name="slug" value="{{ $tenant->slug }}" required>
            </div>
            <button class="button" type="submit">Guardar cliente</button>
        </form>
    </article>

    <article class="card">
        <div class="toolbar">
            <div>
                <small class="eyebrow">Resumen</small>
                <h3>Responsable y fechas</h3>
            </div>
        </div>

        <div class="meta-list">
            <div class="meta-row">
                <span class="muted">Responsable</span>
                <strong>{{ $owner?->name ?: 'Sin responsable' }}</strong>
            </div>
            <div class="meta-row">
                <span class="muted">Correo</span>
                <strong>{{ $owner?->email ?: 'Sin correo' }}</strong>
            </div>
            <div class="meta-row">
                <span class="muted">Creado</span>
                <strong>{{ $tenant->created_at?->format('M j, Y · g:i A') }}</strong>
            </div>
            <div class="meta-row">
                <span class="muted">Ultima actividad</span>
                <strong>{{ $stats['lastEventAt'] ? \Carbon\Carbon::parse($stats['lastEventAt'])->format('M j, Y · g:i A') : 'Sin actividad registrada' }}</strong>
            </div>
        </div>
    </article>
</section>

<section class="grid-2">
    <article class="card">
        <div class="toolbar">
            <div>
                <small class="eyebrow">Sucursales</small>
                <h3>Operacion por punto de venta</h3>
            </div>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Sucursal</th>
                    <th>Estado</th>
                    <th>Catalogo</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($stores as $store)
                    <tr>
                        <td>
                            <strong>{{ $store->name }}</strong>
                            <div class="muted">{{ $store->code }}</div>
                        </td>
                        <td><span class="pill {{ $store->is_active ? 'success' : 'danger' }}">{{ $store->is_active ? 'Activa' : 'Inactiva' }}</span></td>
                        <td>v{{ $store->catalog_version }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3"><div class="surface">Sin sucursales registradas.</div></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </article>

    <article class="card">
        <div class="toolbar">
            <div>
                <small class="eyebrow">Usuarios</small>
                <h3>Accesos del cliente</h3>
            </div>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Rol</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>
                            <strong>{{ $user->name }}</strong>
                            <div class="muted">{{ $user->email }}</div>
                        </td>
                        <td>{{ $user->role }}</td>
                        <td><span class="pill {{ $user->is_active ? 'success' : 'danger' }}">{{ $user->is_active ? 'Activo' : 'Inactivo' }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3"><div class="surface">Sin usuarios registrados.</div></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </article>
</section>
@endsection
