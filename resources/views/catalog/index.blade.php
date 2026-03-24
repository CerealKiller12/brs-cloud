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
    .catalog-search input {
        min-width: 320px;
    }
    .catalog-table-card {
        display: grid;
        gap: 18px;
    }
    .catalog-table-card table {
        table-layout: fixed;
    }
    .catalog-row.is-hidden {
        display: none;
    }
    .catalog-inline-input {
        width: 100%;
        padding: 10px 12px;
        border-radius: 12px;
        border: 1px solid var(--line);
        background: #fff;
        color: var(--text);
        font: inherit;
    }
    .catalog-inline-input.compact {
        max-width: 112px;
    }
    .catalog-meta {
        display: grid;
        gap: 4px;
        margin-top: 6px;
    }
    .catalog-actions {
        display: flex;
        gap: 8px;
        justify-content: flex-end;
        align-items: center;
        flex-wrap: wrap;
    }
    .catalog-filter-empty {
        display: none;
        border: 1px dashed var(--line);
        border-radius: 22px;
        padding: 18px;
        background: linear-gradient(180deg, rgba(255,255,255,.95) 0%, rgba(246,250,253,.92) 100%);
    }
    .catalog-filter-empty.is-visible {
        display: block;
    }
    .catalog-modal-shell {
        position: fixed;
        inset: 0;
        background: rgba(16, 28, 39, .55);
        display: none;
        align-items: center;
        justify-content: center;
        padding: 24px;
        z-index: 1200;
    }
    .catalog-modal-shell.is-open {
        display: flex;
    }
    .catalog-modal-card {
        width: min(860px, 100%);
        max-height: calc(100vh - 48px);
        overflow: auto;
        background: rgba(255,255,255,.99);
        border: 1px solid var(--line);
        border-radius: 28px;
        box-shadow: 0 24px 60px rgba(18, 34, 48, .16);
        padding: 24px;
    }
    .catalog-modal-head {
        display: flex;
        justify-content: space-between;
        gap: 18px;
        align-items: start;
        margin-bottom: 18px;
    }
    .catalog-modal-close {
        width: 40px;
        height: 40px;
        border-radius: 999px;
        border: 1px solid var(--line);
        background: var(--panel-soft);
        color: var(--text);
        cursor: pointer;
        font-size: 22px;
        line-height: 1;
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
                    <small class="eyebrow">Nuevo producto</small>
                    <h3>Agrega un producto al catalogo</h3>
                    <p>Crea un producto nuevo para que aparezca en todas las cajas conectadas a esta sucursal.</p>
                </div>
                <span class="pill">Alta manual</span>
            </div>

            <form method="POST" action="{{ route('catalog.store') }}" class="grid grid-2">
                @csrf

                <div class="field">
                    <label for="sku">SKU</label>
                    <input id="sku" name="sku" value="{{ old('sku') }}" required>
                </div>
                <div class="field">
                    <label for="barcode">Codigo de barras</label>
                    <input id="barcode" name="barcode" value="{{ old('barcode') }}">
                </div>
                <div class="field" style="grid-column: 1 / -1;">
                    <label for="name">Nombre del producto</label>
                    <input id="name" name="name" value="{{ old('name') }}" required>
                </div>
                <div class="field">
                    <label for="price">Precio</label>
                    <input id="price" name="price" type="number" step="0.01" min="0" value="{{ old('price') }}" required>
                </div>
                <div class="field">
                    <label for="cost">Costo</label>
                    <input id="cost" name="cost" type="number" step="0.01" min="0" value="{{ old('cost') }}">
                </div>
                <div class="field">
                    <label for="stock_on_hand">Stock disponible</label>
                    <input id="stock_on_hand" name="stock_on_hand" type="number" min="0" value="{{ old('stock_on_hand', 0) }}" required>
                </div>
                <div class="field">
                    <label for="reorder_point">Punto de reorden</label>
                    <input id="reorder_point" name="reorder_point" type="number" min="0" value="{{ old('reorder_point', 0) }}" required>
                </div>
                <div class="surface" style="grid-column: 1 / -1; display: flex; align-items: center; gap: 10px;">
                    <input id="track_inventory" name="track_inventory" type="checkbox" value="1" {{ old('track_inventory', true) ? 'checked' : '' }} style="width: auto;">
                    <label for="track_inventory" style="margin: 0;">Controlar inventario desde la nube</label>
                </div>
                <div class="row-actions" style="grid-column: 1 / -1;">
                    <button class="button" type="submit">Crear producto</button>
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
                <span class="pill">Version {{ $store->catalog_version }}</span>
                <span class="pill">{{ $store->timezone }}</span>
            </div>

            <div class="catalog-panel">
                <div class="surface">
                    <small class="eyebrow">Sincronizacion</small>
                    <strong style="display:block; font-size: 18px; margin-bottom: 4px;">Catalogo actualizado automaticamente</strong>
                    <p>Si una caja o esta misma pagina cambia productos o existencias, esta vista se mantiene escuchando la version mas reciente.</p>
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
            <form method="GET" action="{{ route('catalog.index') }}" class="catalog-search" onsubmit="return false;">
                <input
                    name="q"
                    value="{{ $search }}"
                    placeholder="Buscar por nombre, SKU o codigo"
                    list="catalog-suggestions"
                    data-catalog-filter-input
                    autocomplete="off">
                <datalist id="catalog-suggestions">
                    @foreach ($catalogSuggestions as $suggestion)
                        <option value="{{ $suggestion }}"></option>
                    @endforeach
                </datalist>
                <span class="muted" style="font-size: 13px;">Se va filtrando mientras escribes.</span>
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
            <tbody data-catalog-rows>
                @forelse ($catalog as $item)
                    @php($isLowStock = (bool) $item->track_inventory && (int) $item->stock_on_hand <= (int) $item->reorder_point)
                    @php($inlineFormId = 'quick-edit-'.$item->id)
                    @php($priceValue = number_format($item->price_cents / 100, 2, '.', ''))
                    @php($costValue = $item->cost_cents ? number_format($item->cost_cents / 100, 2, '.', '') : '')
                    <tr
                        class="catalog-row"
                        data-catalog-row
                        data-search="{{ mb_strtolower(collect([$item->name, $item->sku, $item->barcode])->filter()->implode(' ')) }}"
                        data-product-id="{{ $item->id }}"
                        data-update-url="{{ route('catalog.update', $item->id) }}"
                        data-sku="{{ $item->sku }}"
                        data-barcode="{{ $item->barcode }}"
                        data-name="{{ $item->name }}"
                        data-price="{{ $priceValue }}"
                        data-cost="{{ $costValue }}"
                        data-stock="{{ $item->stock_on_hand }}"
                        data-reorder="{{ $item->reorder_point }}"
                        data-track="{{ $item->track_inventory ? 1 : 0 }}"
                        data-active="{{ $item->is_active ? 1 : 0 }}">
                        <td>
                            <input
                                class="catalog-inline-input"
                                form="{{ $inlineFormId }}"
                                name="name"
                                value="{{ $item->name }}"
                                aria-label="Nombre del producto">
                            <div class="catalog-meta">
                                <span class="muted">{{ $item->sku }}{{ $item->barcode ? ' · '.$item->barcode : '' }}</span>
                            </div>
                        </td>
                        <td>
                            <input
                                class="catalog-inline-input compact"
                                form="{{ $inlineFormId }}"
                                name="price"
                                type="number"
                                step="0.01"
                                min="0"
                                value="{{ $priceValue }}"
                                aria-label="Precio">
                            @if ($item->cost_cents)
                                <div class="catalog-meta">
                                    <span class="muted">Costo MX${{ number_format($item->cost_cents / 100, 2) }}</span>
                                </div>
                            @endif
                        </td>
                        <td>
                            <input
                                class="catalog-inline-input compact"
                                form="{{ $inlineFormId }}"
                                name="stock_on_hand"
                                type="number"
                                min="0"
                                value="{{ $item->stock_on_hand }}"
                                aria-label="Stock disponible">
                            @if ($item->track_inventory)
                                <div class="catalog-meta">
                                    <span class="pill {{ $isLowStock ? 'danger stock-pill low' : '' }}">Reorden {{ $item->reorder_point }}</span>
                                </div>
                            @endif
                        </td>
                        <td><span class="pill {{ $item->is_active ? 'success' : '' }}">{{ $item->is_active ? 'Activo' : 'Pausado' }}</span></td>
                        <td>v{{ $item->catalog_version }}</td>
                        <td>
                            <form id="{{ $inlineFormId }}" method="POST" action="{{ route('catalog.update', $item->id) }}" style="display:none;">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="sku" value="{{ $item->sku }}">
                                <input type="hidden" name="barcode" value="{{ $item->barcode }}">
                                <input type="hidden" name="cost" value="{{ $costValue }}">
                                <input type="hidden" name="reorder_point" value="{{ $item->reorder_point }}">
                                <input type="hidden" name="track_inventory" value="{{ $item->track_inventory ? 1 : 0 }}">
                                <input type="hidden" name="is_active" value="{{ $item->is_active ? 1 : 0 }}">
                            </form>
                            <div class="catalog-actions">
                                <button class="button-secondary" type="submit" form="{{ $inlineFormId }}">Guardar</button>
                                <button
                                    class="pill"
                                    type="button"
                                    data-catalog-edit-open
                                    data-product-id="{{ $item->id }}">
                                    Editar
                                </button>
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
                        <td colspan="6"><div class="empty" data-catalog-empty>No encontramos productos con ese criterio. Prueba con otro nombre, SKU o codigo.</div></td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="catalog-filter-empty" data-catalog-filter-empty>
            <strong style="display:block; margin-bottom: 6px;">No encontramos coincidencias en esta pagina.</strong>
            <span class="muted">Prueba con otra palabra, SKU o codigo para ubicar el producto mas rapido.</span>
        </div>

        <div class="pagination">{{ $catalog->links() }}</div>
    </section>
</section>

<div class="catalog-modal-shell" data-catalog-modal aria-hidden="true">
    <div class="catalog-modal-card">
        <div class="catalog-modal-head">
            <div>
                <small class="eyebrow">Edicion completa</small>
                <h3>Actualiza este producto</h3>
                <p>Usa esta ventana cuando necesites tocar mas que nombre, precio o existencias.</p>
            </div>
            <button class="catalog-modal-close" type="button" data-catalog-edit-close aria-label="Cerrar">&times;</button>
        </div>

        <form method="POST" action="{{ route('catalog.update', 0) }}" class="grid grid-2" data-catalog-modal-form>
            @csrf
            @method('PUT')
            <input type="hidden" name="edit_product_id" value="">
            <div class="field">
                <label for="modal_sku">SKU</label>
                <input id="modal_sku" name="sku" required>
            </div>
            <div class="field">
                <label for="modal_barcode">Codigo de barras</label>
                <input id="modal_barcode" name="barcode">
            </div>
            <div class="field" style="grid-column: 1 / -1;">
                <label for="modal_name">Nombre del producto</label>
                <input id="modal_name" name="name" required>
            </div>
            <div class="field">
                <label for="modal_price">Precio</label>
                <input id="modal_price" name="price" type="number" step="0.01" min="0" required>
            </div>
            <div class="field">
                <label for="modal_cost">Costo</label>
                <input id="modal_cost" name="cost" type="number" step="0.01" min="0">
            </div>
            <div class="field">
                <label for="modal_stock_on_hand">Stock disponible</label>
                <input id="modal_stock_on_hand" name="stock_on_hand" type="number" min="0" required>
            </div>
            <div class="field">
                <label for="modal_reorder_point">Punto de reorden</label>
                <input id="modal_reorder_point" name="reorder_point" type="number" min="0" required>
            </div>
            <div class="surface" style="grid-column: 1 / -1; display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <input id="modal_track_inventory" name="track_inventory" type="checkbox" value="1" style="width: auto;">
                <label for="modal_track_inventory" style="margin: 0;">Controlar inventario desde la nube</label>
            </div>
            <div class="surface" style="grid-column: 1 / -1; display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <input id="modal_is_active" name="is_active" type="checkbox" value="1" style="width: auto;">
                <label for="modal_is_active" style="margin: 0;">Mostrar este producto en las cajas</label>
            </div>
            <div class="row-actions" style="grid-column: 1 / -1;">
                <button class="button-secondary" type="button" data-catalog-edit-close>Cancelar</button>
                <button class="button" type="submit">Guardar cambios</button>
            </div>
        </form>
    </div>
</div>

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
    const filterInput = document.querySelector('[data-catalog-filter-input]');
    const rows = Array.from(root.querySelectorAll('[data-catalog-row]'));
    const modal = document.querySelector('[data-catalog-modal]');
    const modalForm = document.querySelector('[data-catalog-modal-form]');
    const filterEmpty = root.querySelector('[data-catalog-filter-empty]');
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
            setStatus('Esperando reconexion del catalogo compartido...');
        };
    };

    const filterRows = () => {
        if (!filterInput || !rows.length) {
            return;
        }

        const query = filterInput.value.trim().toLowerCase();
        let visibleCount = 0;

        rows.forEach((row) => {
            const haystack = row.dataset.search || '';
            const matches = query === '' || haystack.includes(query);
            row.classList.toggle('is-hidden', !matches);
            if (matches) {
                visibleCount += 1;
            }
        });

        if (filterEmpty) {
            filterEmpty.classList.toggle('is-visible', rows.length > 0 && visibleCount === 0);
        }
    };

    const closeModal = () => {
        if (!modal) {
            return;
        }

        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
    };

    const openModalForRow = (row) => {
        if (!modal || !modalForm || !row) {
            return;
        }

        modalForm.action = row.dataset.updateUrl || modalForm.action;
        modalForm.querySelector('[name="edit_product_id"]').value = row.dataset.productId || '';
        modalForm.querySelector('[name="sku"]').value = row.dataset.sku || '';
        modalForm.querySelector('[name="barcode"]').value = row.dataset.barcode || '';
        modalForm.querySelector('[name="name"]').value = row.dataset.name || '';
        modalForm.querySelector('[name="price"]').value = row.dataset.price || '';
        modalForm.querySelector('[name="cost"]').value = row.dataset.cost || '';
        modalForm.querySelector('[name="stock_on_hand"]').value = row.dataset.stock || '';
        modalForm.querySelector('[name="reorder_point"]').value = row.dataset.reorder || '';
        modalForm.querySelector('[name="track_inventory"]').checked = row.dataset.track === '1';
        modalForm.querySelector('[name="is_active"]').checked = row.dataset.active === '1';

        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
    };

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            connect();
        }
    });

    window.addEventListener('focus', () => {
        connect();
    });

    if (filterInput) {
        filterInput.addEventListener('input', filterRows);
        filterRows();
    }

    root.querySelectorAll('[data-catalog-edit-open]').forEach((button) => {
        button.addEventListener('click', () => {
            const row = button.closest('[data-catalog-row]');
            openModalForRow(row);
        });
    });

    document.querySelectorAll('[data-catalog-edit-close]').forEach((button) => {
        button.addEventListener('click', closeModal);
    });

    if (modal) {
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal();
            }
        });
    }

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeModal();
        }
    });

    connect();
})();
</script>
@endpush
@endsection
