@extends('layouts.admin', ['title' => 'Admin Console | BRS'])

@section('content')
<section class="hero">
    <small>Administracion interna</small>
    <h2>Control general de clientes y subscripciones</h2>
    <p>Desde aqui administras los negocios de BRS Cloud sin mezclar esa operacion con el portal que ve cada cliente.</p>
</section>

<section class="metrics-grid">
    <article class="stat">
        <div class="stat-label">Clientes</div>
        <div class="stat-value">{{ $stats['tenants'] }}</div>
        <div class="stat-note">{{ $stats['activeTenants'] }} activos en la plataforma</div>
    </article>
    <article class="stat">
        <div class="stat-label">Trialing</div>
        <div class="stat-value">{{ $stats['trialingTenants'] }}</div>
        <div class="stat-note">negocios todavia en prueba</div>
    </article>
    <article class="stat">
        <div class="stat-label">Con Cobro</div>
        <div class="stat-value">{{ $stats['paidTenants'] }}</div>
        <div class="stat-note">planes activos o en gracia</div>
    </article>
    <article class="stat">
        <div class="stat-label">En Riesgo</div>
        <div class="stat-value">{{ $stats['attentionTenants'] }}</div>
        <div class="stat-note">past due, canceled o inactivos</div>
    </article>
</section>

<section class="grid-3">
    <article class="card">
        <div class="toolbar">
            <div>
                <small class="eyebrow">Altas recientes</small>
                <h3>Clientes nuevos</h3>
            </div>
            <a class="button-secondary" href="{{ route('admin.clients.index') }}">Ver todos</a>
        </div>

        <div class="meta-list">
            @forelse ($recentTenants as $tenant)
                <div class="meta-row">
                    <div>
                        <strong>{{ $tenant->name }}</strong>
                        <div class="muted">{{ $tenant->owner_email ?: 'Sin correo principal' }}</div>
                    </div>
                    <div style="text-align:right;">
                        <span class="pill {{ $tenant->status_pill }}">{{ $tenant->subscription_status }}</span>
                        <div class="muted">{{ optional($tenant->created_at)->format('M j, Y') }}</div>
                    </div>
                </div>
            @empty
                <div class="surface">
                    <strong>No hay clientes recientes.</strong>
                </div>
            @endforelse
        </div>
    </article>

    <article class="card">
        <div class="toolbar">
            <div>
                <small class="eyebrow">Atencion</small>
                <h3>Necesitan seguimiento</h3>
            </div>
            <a class="button-secondary" href="{{ route('admin.subscriptions.index', ['status' => 'past_due']) }}">Filtrar</a>
        </div>

        <div class="meta-list">
            @forelse ($attentionTenants as $tenant)
                <div class="meta-row">
                    <div>
                        <strong>{{ $tenant->name }}</strong>
                        <div class="muted">{{ $tenant->plan_code }} · {{ $tenant->owner_name ?: 'Sin responsable' }}</div>
                    </div>
                    <div style="text-align:right;">
                        <span class="pill {{ $tenant->status_pill }}">{{ $tenant->subscription_status }}</span>
                        <div class="muted">{{ $tenant->trial_ends_at?->format('M j, Y') ?: 'Sin trial' }}</div>
                    </div>
                </div>
            @empty
                <div class="surface">
                    <strong>No hay clientes con alerta inmediata.</strong>
                </div>
            @endforelse
        </div>
    </article>

    <article class="card">
        <div class="toolbar">
            <div>
                <small class="eyebrow">Actividad</small>
                <h3>Uso de la plataforma</h3>
            </div>
        </div>

        <div class="meta-list">
            <div class="meta-row">
                <span class="muted">Sucursales registradas</span>
                <strong>{{ $stats['stores'] }}</strong>
            </div>
            <div class="meta-row">
                <span class="muted">Cajas registradas</span>
                <strong>{{ $stats['devices'] }}</strong>
            </div>
            <div class="meta-row">
                <span class="muted">Usuarios cloud</span>
                <strong>{{ $stats['users'] }}</strong>
            </div>
            <div class="meta-row">
                <span class="muted">Eventos sincronizados</span>
                <strong>{{ number_format($stats['syncEvents']) }}</strong>
            </div>
        </div>
    </article>
</section>
@endsection
