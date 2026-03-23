@extends('layouts.app', ['title' => 'Catalogo Compartido | BRS Cloud'])

@push('head')
<style>
    .catalog-shell {
        display: grid;
        gap: 18px;
    }
    .catalog-top {
        display: grid;
        grid-template-columns: minmax(0, .95fr) minmax(0, 1.05fr);
        gap: 18px;
    }
    .catalog-editor {
        background:
            radial-gradient(circle at top right, rgba(223, 193, 158, .18), transparent 34%),
            linear-gradient(180deg, rgba(255,255,255,.99) 0%, rgba(246,250,253,.99) 100%);
    }
    .catalog-store-card {
        display: grid;
        gap: 16px;
    }
    .catalog-badges {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        align-items: center;
    }
    .catalog-summary {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
    }
    .catalog-summary-card {
        padding: 18px;
        border-radius: 22px;
        border: 1px solid var(--line);
        background: linear-gradient(180deg, rgba(255,255,255,.98) 0%, rgba(246,250,253,.98) 100%);
    }
    .catalog-summary-card .stat-value {
        font-size: 32px;
        margin-bottom: 4px;
    }
    .catalog-panel {
        display: grid;
        gap: 14px;
    }
    .catalog-search {
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
    }
    .catalog-table-card {
        display: grid;
        gap: 18px;
    }
    .stock-pill.low {
        background: #fff0ec;
        color: #9d4635;
        border-color: #f6c9bf;
    }
    @media (max-width: 1180px) {
        .catalog-top,
        .catalog-summary {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<section class="hero">
    <small>Catalogo compartido</small>
    <h2>El inventario que comparten todas tus cajas</h2>
    <p>Administra productos, stock y precios desde una sola vista para que todos tus puntos de venta trabajen con el mismo catalogo.</p>
</section>

@if (session('status'))
    <section class="notice success">{{ session('status') }}</section>
@endif

@if ($errors->any())
    <section class="notice danger">{{ $errors->first() }}</section>
@endif

<section class="catalog-summary">
    <article class="catalog-summary-card">
        <div class="stat-label">Productos</div>
        <div class="stat-value">{{ $catalogStats['total'] }}</div>
        <div class="stat-note">registrados en esta sucursal</div>
    </article>
    <article class="catalog-summary-card">
        <div class="stat-label">Activos</div>
        <div class="stat-value">{{ $catalogStats['active'] }}</div>
        <div class="stat-note">visibles para tus cajas</div>
    </article>
    <article class="catalog-summary-card">
        <div class="stat-label">Pausados</div>
        <div class="stat-value">{{ $catalogStats['inactive'] }}</div>
        <div class="stat-note">fuera de operacion temporalmente</div>
    </article>
    <article class="catalog-summary-card">
        <div class="stat-label">Stock bajo</div>
        <div class="stat-value">{{ $catalogStats['lowStock'] }}</div>
        <div class="stat-note">requieren reabastecimiento</div>
    </article>
</section>

<section class="catalog-shell">
    <div class="catalog-top">
        <article class="card catalog-editor">
            <div class="toolbar">
                <div>
                    <small class="eyebrow">Editor</small>
                    <h3>{{ $editProduct ? 'Actualiza este producto' : 'Agrega un producto al catalogo' }}</h3>
                    <p>{{ $editProduct ? 'Modifica precio, inventario o informacion base sin salir de la nube.' : 'Crea un producto nuevo para que aparezca en todas las cajas conectadas a esta sucursal.' }}</p>
                </div>
                @if ($editProduct)
                    <a class="pill" href="{{ route('catalog.index') }}">Cancelar</a>
                @endif
            </div>

            <form method="POST" action="{{ $editProduct ? route('catalog.update', $editProduct->id) : route('catalog.store') }}" class="grid grid-2">
                @csrf
                @if ($editProduct)
                    @method('PUT')
                @endif

                <div class="field">
                    <label for="sku">SKU</label>
                    <input id="sku" name="sku" value="{{ old('sku', $editProduct->sku ?? '') }}" required>
                </div>
                <div class="field">
                    <label for="barcode">Codigo de barras</label>
                    <input id="barcode" name="barcode" value="{{ old('barcode', $editProduct->barcode ?? '') }}">
                </div>
                <div class="field" style="grid-column: 1 / -1;">
                    <label for="name">Nombre del producto</label>
                    <input id="name" name="name" value="{{ old('name', $editProduct->name ?? '') }}" required>
                </div>
                <div class="field">
                    <label for="price">Precio</label>
                    <input id="price" name="price" type="number" step="0.01" min="0" value="{{ old('price', isset($editProduct) ? number_format($editProduct->price_cents / 100, 2, '.', '') : '') }}" required>
                </div>
                <div class="field">
                    <label for="cost">Costo</label>
                    <input id="cost" name="cost" type="number" step="0.01" min="0" value="{{ old('cost', isset($editProduct) ? number_format($editProduct->cost_cents / 100, 2, '.', '') : '') }}">
                </div>
                <div class="field">
                    <label for="stock_on_hand">Stock disponible</label>
                    <input id="stock_on_hand" name="stock_on_hand" type="number" min="0" value="{{ old('stock_on_hand', $editProduct->stock_on_hand ?? 0) }}" required>
                </div>
                <div class="field">
                    <label for="reorder_point">Punto de reorden</label>
                    <input id="reorder_point" name="reorder_point" type="number" min="0" value="{{ old('reorder_point', $editProduct->reorder_point ?? 0) }}" required>
                </div>
                <div class="surface" style="grid-column: 1 / -1; display: flex; align-items: center; gap: 10px;">
                    <input id="track_inventory" name="track_inventory" type="checkbox" value="1" {{ old('track_inventory', $editProduct->track_inventory ?? true) ? 'checked' : '' }} style="width: auto;">
                    <label for="track_inventory" style="margin: 0;">Controlar inventario desde la nube</label>
                </div>
                <div class="row-actions" style="grid-column: 1 / -1;">
                    <button class="button" type="submit">{{ $editProduct ? 'Guardar cambios' : 'Crear producto' }}</button>
                </div>
            </form>
        </article>

        <article class="card catalog-store-card">
            <div>
                <small class="eyebrow">Sucursal activa</small>
                <h3>{{ $store->name }}</h3>
                <p>{{ data_get($store->branding_json, 'business_name', 'Negocio sin nombre visible') }}</p>
            </div>

            <div class="catalog-badges">
                <span class="pill">Referencia {{ $store->code }}</span>
                <span class="pill">Catalogo version {{ $store->catalog_version }}</span>
                <span class="pill">{{ $store->timezone }}</span>
            </div>

            <div class="catalog-panel">
                <div class="surface">
                    <small class="eyebrow">Sincronizacion</small>
                    <strong style="display:block; font-size: 18px; margin-bottom: 4px;">Catalogo actualizado automaticamente</strong>
                    <p>Si una caja o esta misma nube cambia productos o stock, esta vista se mantiene escuchando la version mas reciente.</p>
                </div>
                <div class="surface">
                    <small class="eyebrow">Ultima actualizacion</small>
                    <strong style="display:block; font-size: 18px; margin-bottom: 4px;">
                        {{ $catalogStats['lastUpdatedAt'] ? \Carbon\Carbon::parse($catalogStats['lastUpdatedAt'])->format('M j, Y · g:i A') : 'Sin cambios recientes' }}
                    </strong>
                    <p>Esto te ayuda a saber que tan fresco esta el catalogo de esta sucursal.</p>
                </div>
            </div>
        </article>
    </div>

    <section
        class="card catalog-table-card"
        data-cloud-catalog-live
        data-catalog-version="{{ (int) $store->catalog_version }}"
        data-store-id="{{ (int) $store->id }}"
        data-events-url="{{ route('catalog.events', ['store_id' => $store->id]) }}">
        <div class="toolbar">
            <div>
                <small class="eyebrow">Inventario</small>
                <h3>Productos disponibles en esta sucursal</h3>
                <p class="muted" data-live-status style="margin-top: 6px;">Actualizacion automatica activa.</p>
            </div>
            <form method="GET" action="{{ route('catalog.index') }}" class="catalog-search">
                <input name="q" value="{{ $search }}" placeholder="Buscar por nombre, SKU o codigo" style="width: 320px;">
                <button class="button" type="submit">Buscar</button>
            </form>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Precio</th>
                    <th>Stock</th>
                    <th>Estado</th>
                    <th>Version</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($catalog as $item)
                    @php($isLowStock = (bool) $item->track_inventory && (int) $item->stock_on_hand <= (int) $item->reorder_point)
                    <tr>
                        <td>
                            <strong>{{ $item->name }}</strong><br>
                            <span class="muted">{{ $item->sku }}{{ $item->barcode ? ' · '.$item->barcode : '' }}</span>
                        </td>
                        <td>
                            <strong>MX${{ number_format($item->price_cents / 100, 2) }}</strong>
                            @if ($item->cost_cents)
                                <br><span class="muted">Costo MX${{ number_format($item->cost_cents / 100, 2) }}</span>
                            @endif
                        </td>
                        <td>
                            <span class="pill {{ $isLowStock ? 'danger stock-pill low' : '' }}">{{ $item->stock_on_hand }}</span>
                            @if ($item->track_inventory)
                                <br><span class="muted">Reorden {{ $item->reorder_point }}</span>
                            @endif
                        </td>
                        <td><span class="pill {{ $item->is_active ? 'success' : '' }}">{{ $item->is_active ? 'Activo' : 'Pausado' }}</span></td>
                        <td>v{{ $item->catalog_version }}</td>
                        <td>
                            <div style="display: flex; gap: 10px; justify-content: flex-end; align-items: center; flex-wrap: wrap;">
                                <a class="pill" href="{{ route('catalog.index', ['edit' => $item->id] + ($search ? ['q' => $search] : [])) }}">Editar</a>
                                <form method="POST" action="{{ route('catalog.toggle', $item->id) }}">
                                    @csrf
                                    <button class="pill" type="submit" style="cursor: pointer;">{{ $item->is_active ? 'Pausar' : 'Reactivar' }}</button>
                                </form>
                                <form method="POST" action="{{ route('catalog.destroy', $item->id) }}" onsubmit="return confirm('Se eliminara {{ addslashes($item->name) }} del catalogo cloud. ¿Continuar?')">
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        type="submit"
                                        aria-label="Eliminar producto"
                                        title="Eliminar producto"
                                        style="width: 34px; height: 34px; display: inline-grid; place-items: center; border-radius: 999px; border: 1px solid #e8c4bc; background: #fff1ee; color: #ae4c3b; cursor: pointer;">
                                        <svg viewBox="0 0 24 24" aria-hidden="true" style="width: 16px; height: 16px; fill: currentColor;">
                                            <path d="M9 3h6l1 2h4v2H4V5h4l1-2Zm-1 6h2v8H8V9Zm6 0h2v8h-2V9ZM7 9h10l-.8 10.2A2 2 0 0 1 14.2 21H9.8a2 2 0 0 1-1.99-1.8L7 9Z" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6"><div class="empty">No hay productos que coincidan con la busqueda actual.</div></td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="pagination">{{ $catalog->links() }}</div>
    </section>
</section>

@push('scripts')
<script>
(() => {
    const root = document.querySelector('[data-cloud-catalog-live]');

    if (!root) {
        return;
    }

    let currentVersion = Number(root.dataset.catalogVersion || '0');
    const eventsUrl = root.dataset.eventsUrl || '';
    const liveStatus = root.querySelector('[data-live-status]');
    let source = null;

    const setStatus = (text) => {
        if (liveStatus) {
            liveStatus.textContent = text;
        }
    };

    const connect = () => {
        if (!eventsUrl || typeof EventSource === 'undefined') {
            setStatus('Actualizacion automatica no disponible en este navegador.');
            return;
        }

        if (source) {
            source.close();
        }

        source = new EventSource(eventsUrl, { withCredentials: true });
        setStatus(`Escuchando cambios del catalogo v${currentVersion}...`);

        source.addEventListener('catalog.version', (event) => {
            const payload = JSON.parse(event.data || '{}');
            const nextVersion = Number(payload.catalogVersion || 0);

            if (nextVersion > currentVersion) {
                setStatus(`Aplicando cambios del catalogo v${nextVersion}...`);
                window.location.reload();
                return;
            }

            currentVersion = nextVersion;
            setStatus(`Catalogo al dia en v${currentVersion}.`);
        });

        source.addEventListener('heartbeat', (event) => {
            const payload = JSON.parse(event.data || '{}');
            currentVersion = Number(payload.catalogVersion || currentVersion);
            setStatus(`Catalogo al dia en v${currentVersion}.`);
        });

        source.onerror = () => {
            setStatus('Esperando reconexion del catalogo cloud...');
        };
    };

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            connect();
        }
    });

    window.addEventListener('focus', () => {
        connect();
    });

    connect();
})();
</script>
@endpush
@endsection
