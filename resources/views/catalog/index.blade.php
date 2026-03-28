@extends('layouts.app', ['title' => 'Catalogo Compartido | Venpi Cloud'])

@push('head')
<style>
    .catalog-shell {
        display: grid;
        gap: 18px;
    }
    .catalog-top {
        display: grid;
        grid-template-columns: 1fr;
        gap: 18px;
    }
    .catalog-editor {
        background:
            radial-gradient(circle at top right, rgba(223, 193, 158, .18), transparent 34%),
            linear-gradient(180deg, rgba(255,255,255,.99) 0%, rgba(246,250,253,.99) 100%);
    }
    .catalog-editor-body {
        display: grid;
        grid-template-columns: minmax(0, 1.35fr) minmax(280px, .65fr);
        gap: 18px;
        align-items: start;
    }
    .catalog-editor-section {
        display: grid;
        gap: 14px;
    }
    .catalog-section-head {
        display: grid;
        gap: 4px;
    }
    .catalog-section-head p {
        font-size: 14px;
    }
    .catalog-editor-fields {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px 16px;
    }
    .catalog-editor-fields .field {
        margin-bottom: 0;
    }
    .catalog-field-span {
        grid-column: 1 / -1;
    }
    .catalog-editor-side {
        display: grid;
        gap: 14px;
        align-content: start;
    }
    .catalog-toggle-card {
        display: grid;
        gap: 10px;
    }
    .catalog-toggle-control {
        display: flex;
        align-items: start;
        gap: 12px;
        margin: 0;
    }
    .catalog-toggle-control input {
        width: auto;
        margin-top: 2px;
        flex: none;
    }
    .catalog-toggle-copy {
        display: grid;
        gap: 4px;
    }
    .catalog-toggle-copy strong {
        font-size: 15px;
        color: var(--text);
    }
    .catalog-editor-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }
    .catalog-editor-actions .button {
        min-width: 180px;
    }
    .catalog-editor-tips {
        display: grid;
        gap: 10px;
    }
    .catalog-editor-tip {
        display: grid;
        gap: 2px;
    }
    .catalog-editor-tip strong {
        font-size: 14px;
        color: var(--text);
    }
    .catalog-editor-tip span {
        font-size: 13px;
        color: var(--muted);
    }
    .catalog-store-card {
        display: grid;
        gap: 16px;
        grid-template-columns: minmax(0, .8fr) minmax(0, 1.2fr);
        align-items: start;
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
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .catalog-store-overview {
        display: grid;
        gap: 14px;
        align-content: start;
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
    .catalog-action {
        min-height: 40px;
        padding: 0 16px;
        border-radius: 14px;
        border: 1px solid var(--line);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        background: var(--soft);
        color: #3d566d;
        line-height: 1;
    }
    .catalog-action.is-hidden {
        display: none;
    }
    .catalog-action.primary {
        background: var(--accent);
        color: #fff;
        border-color: var(--accent);
    }
    .catalog-action.secondary {
        background: var(--soft);
        color: #3d566d;
    }
    .catalog-action.compact {
        min-height: 36px;
        padding: 0 14px;
        font-size: 13px;
    }
    .catalog-action.danger {
        width: 36px;
        min-width: 36px;
        padding: 0;
        border-radius: 999px;
        background: #fff1ee;
        border-color: #e8c4bc;
        color: #ae4c3b;
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
    .catalog-transfer-note {
        display: grid;
        gap: 6px;
        padding: 14px 16px;
        border-radius: 18px;
        border: 1px solid var(--line);
        background: var(--panel-soft);
    }
    .catalog-modifier-list {
        display: grid;
        gap: 10px;
    }
    .catalog-modifier-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 150px 40px;
        gap: 10px;
        align-items: end;
    }
    .catalog-modifier-remove {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        border: 1px solid #e8c4bc;
        background: #fff1ee;
        color: #ae4c3b;
        cursor: pointer;
        font-size: 20px;
        line-height: 1;
    }
    .catalog-modifier-add {
        justify-self: start;
    }
    .catalog-modifier-tools {
        display: grid;
        gap: 8px;
        justify-items: start;
        margin-top: 2px;
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
        .catalog-summary,
        .catalog-store-card,
        .catalog-editor-body {
            grid-template-columns: 1fr;
        }

        .catalog-panel {
            grid-template-columns: 1fr;
        }
    }
    @media (max-width: 820px) {
        .catalog-editor-fields {
            grid-template-columns: 1fr;
        }
        .catalog-modifier-row {
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

            <form method="POST" action="{{ route('catalog.store') }}">
                @csrf
                <div class="catalog-editor-body">
                    <div class="surface catalog-editor-section">
                        <div class="catalog-section-head">
                            <small class="eyebrow">Datos base</small>
                            <p>Empieza con los datos que tu equipo usa para ubicar el producto rapido en caja y catalogo.</p>
                        </div>

                        <div class="catalog-editor-fields">
                            <div class="field">
                                <label for="sku">SKU</label>
                                <input id="sku" name="sku" value="{{ old('sku') }}" placeholder="Ej. PEP-355" required>
                            </div>
                            <div class="field">
                                <label for="barcode">Codigo de barras</label>
                                <input id="barcode" name="barcode" value="{{ old('barcode') }}" placeholder="Opcional">
                            </div>
                            <div class="field catalog-field-span">
                                <label for="name">Nombre del producto</label>
                                <input id="name" name="name" value="{{ old('name') }}" placeholder="Ej. Pepsi 355 ml" required>
                            </div>
                        </div>
                    </div>

                    <div class="catalog-editor-side">
                        <div class="surface catalog-editor-section">
                            <div class="catalog-section-head">
                                <small class="eyebrow">Precio y stock</small>
                                <p>Define lo comercial y lo operativo sin bajar hasta el final del formulario.</p>
                            </div>

                            <div class="catalog-editor-fields">
                                <div class="field">
                                    <label for="price">Precio</label>
                                    <input id="price" name="price" type="number" step="0.01" min="0" value="{{ old('price') }}" placeholder="0.00" required>
                                </div>
                                <div class="field">
                                    <label for="cost">Costo</label>
                                    <input id="cost" name="cost" type="number" step="0.01" min="0" value="{{ old('cost') }}" placeholder="0.00">
                                </div>
                                <div class="field">
                                    <label for="stock_on_hand">Stock disponible</label>
                                    <input id="stock_on_hand" name="stock_on_hand" type="number" min="0" value="{{ old('stock_on_hand', 0) }}" required>
                                </div>
                                <div class="field">
                                    <label for="reorder_point">Punto de reorden</label>
                                    <input id="reorder_point" name="reorder_point" type="number" min="0" value="{{ old('reorder_point', 0) }}" required>
                                </div>
                                <div class="field catalog-field-span">
                                    <label>Modificadores</label>
                                    <input type="hidden" id="modifiers_text" name="modifiers_text" value="{{ old('modifiers_text') }}" data-modifier-hidden-input>
                                    <div class="catalog-modifier-list" data-modifier-list data-modifier-seed='@json(old('modifiers_text', ''))'></div>
                                    <div class="catalog-modifier-tools">
                                        <button class="catalog-action secondary compact catalog-modifier-add" type="button" data-modifier-add>Agregar modificador</button>
                                        <span class="muted" style="font-size: 13px;">Captura nombre y costo extra por separado. Si no lleva costo, dejalo en 0.</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="surface catalog-toggle-card">
                            <label class="catalog-toggle-control" for="track_inventory">
                                <input id="track_inventory" name="track_inventory" type="checkbox" value="1" {{ old('track_inventory', true) ? 'checked' : '' }}>
                                <div class="catalog-toggle-copy">
                                    <strong>Controlar inventario desde la nube</strong>
                                    <span>Activalo cuando quieras que existencias y alertas de reorden se reflejen entre cajas.</span>
                                </div>
                            </label>
                        </div>

                        <div class="surface catalog-editor-tips">
                            <div class="catalog-editor-tip">
                                <strong>Alta mas rapida</strong>
                                <span>SKU, nombre y precio son lo minimo para empezar a vender.</span>
                            </div>
                            <div class="catalog-editor-tip">
                                <strong>Mejor control</strong>
                                <span>Si registras costo y punto de reorden, la sucursal detecta faltantes antes.</span>
                            </div>
                        </div>

                        <div class="catalog-editor-actions">
                            <span class="muted" style="font-size: 13px;">Se publica en esta sucursal y sus cajas conectadas.</span>
                            <button class="button" type="submit">Crear producto</button>
                        </div>
                    </div>
                </div>
            </form>
        </article>

        <article class="card catalog-store-card">
            <div class="catalog-store-overview">
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
                    @php($modifiers = collect($item->modifiers ?? []))
                    @php($modifiersText = $modifiers->map(fn ($modifier) => trim((string) ($modifier['name'] ?? '')).(((int) ($modifier['priceDeltaCents'] ?? 0)) > 0 ? '|'.number_format(((int) ($modifier['priceDeltaCents'] ?? 0)) / 100, 2, '.', '') : ''))->filter()->implode("\n"))
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
                        data-active="{{ $item->is_active ? 1 : 0 }}"
                        data-transfer-url="{{ route('catalog.transfer', $item->id) }}"
                        data-modifiers-text='@json($modifiersText)'>
                        <td>
                            <input
                                class="catalog-inline-input"
                                form="{{ $inlineFormId }}"
                                name="name"
                                value="{{ $item->name }}"
                                data-catalog-inline-input
                                aria-label="Nombre del producto">
                            <div class="catalog-meta">
                                <span class="muted">{{ $item->sku }}{{ $item->barcode ? ' · '.$item->barcode : '' }}</span>
                            </div>
                            @if ($modifiers->isNotEmpty())
                                <div class="catalog-meta">
                                    <span class="pill">{{ $modifiers->count() }} modificador(es)</span>
                                    <span class="muted">{{ $modifiers->take(2)->map(fn ($modifier) => trim((string) ($modifier['name'] ?? '')).(((int) ($modifier['priceDeltaCents'] ?? 0)) > 0 ? ' (+MX$'.number_format(((int) ($modifier['priceDeltaCents'] ?? 0)) / 100, 2).')' : ''))->implode(' · ') }}</span>
                                </div>
                            @endif
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
                                data-catalog-inline-input
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
                                data-catalog-inline-input
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
                                <button class="catalog-action primary is-hidden" type="submit" form="{{ $inlineFormId }}" data-catalog-inline-save>Guardar</button>
                                @if ($item->track_inventory && $transferStoreOptions->isNotEmpty())
                                    <button
                                        class="catalog-action secondary compact"
                                        type="button"
                                        data-catalog-transfer-open
                                        data-product-id="{{ $item->id }}">
                                        Mover stock
                                    </button>
                                @endif
                                <button
                                    class="catalog-action secondary compact"
                                    type="button"
                                    data-catalog-edit-open
                                    data-product-id="{{ $item->id }}">
                                    Editar
                                </button>
                                <form method="POST" action="{{ route('catalog.destroy', $item->id) }}" onsubmit="return confirm('Se eliminara {{ addslashes($item->name) }} del catalogo cloud. ¿Continuar?')">
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        class="catalog-action danger compact"
                                        type="submit"
                                        aria-label="Eliminar producto"
                                        title="Eliminar producto">
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
            <div class="field" style="grid-column: 1 / -1;">
                <label>Modificadores</label>
                <input type="hidden" id="modal_modifiers_text" name="modifiers_text" value="" data-modifier-hidden-input>
                <div class="catalog-modifier-list" data-modifier-list data-modifier-seed='""'></div>
                <div class="catalog-modifier-tools">
                    <button class="catalog-action secondary compact catalog-modifier-add" type="button" data-modifier-add>Agregar modificador</button>
                    <span class="muted" style="font-size: 13px;">Captura nombre y costo extra por separado. Si no lleva costo, dejalo en 0.</span>
                </div>
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

<div class="catalog-modal-shell" data-catalog-transfer-modal aria-hidden="true">
    <div class="catalog-modal-card" style="width:min(620px, 100%);">
        <div class="catalog-modal-head">
            <div>
                <small class="eyebrow">Mover stock</small>
                <h3>Enviar existencias a otra sucursal</h3>
                <p>Traspasa unidades del inventario de esta sucursal hacia otra tienda de tu negocio.</p>
            </div>
            <button class="catalog-modal-close" type="button" data-catalog-transfer-close aria-label="Cerrar">&times;</button>
        </div>

        <form method="POST" action="{{ route('catalog.transfer', 0) }}" class="grid" data-catalog-transfer-form>
            @csrf

            <div class="catalog-transfer-note">
                <strong data-catalog-transfer-title>Producto</strong>
                <span class="muted" data-catalog-transfer-stock>Stock disponible: 0</span>
            </div>

            <div class="field">
                <label for="transfer_destination_store_id">Sucursal destino</label>
                <select id="transfer_destination_store_id" name="destination_store_id" required>
                    <option value="">Elige una sucursal</option>
                    @foreach ($transferStoreOptions as $destinationStore)
                        <option value="{{ $destinationStore->id }}">{{ $destinationStore->name }} · {{ $destinationStore->code }}</option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="transfer_quantity">Cantidad a mover</label>
                <input id="transfer_quantity" name="quantity" type="number" min="1" step="1" required>
            </div>

            <div class="row-actions">
                <button class="button-secondary" type="button" data-catalog-transfer-close>Cancelar</button>
                <button class="button" type="submit">Mover stock</button>
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
    const transferModal = document.querySelector('[data-catalog-transfer-modal]');
    const transferForm = document.querySelector('[data-catalog-transfer-form]');
    const filterEmpty = root.querySelector('[data-catalog-filter-empty]');
    let source = null;

    const modifierContainers = Array.from(document.querySelectorAll('[data-modifier-list]'));

    const parseModifierText = (text) => String(text || '')
        .split(/\r?\n/)
        .map((line) => line.trim())
        .filter(Boolean)
        .map((line) => {
            const [name, price = '0'] = line.split('|');
            return {
                name: String(name || '').trim(),
                price: String(price || '0').trim(),
            };
        });

    const syncModifierList = (list) => {
        const wrapper = list.closest('.field') || list.parentElement;
        const hidden = wrapper?.querySelector('[data-modifier-hidden-input]');
        if (!hidden) {
            return;
        }

        const lines = Array.from(list.querySelectorAll('[data-modifier-row]'))
            .map((row) => {
                const name = String(row.querySelector('[data-modifier-name]')?.value || '').trim();
                const price = String(row.querySelector('[data-modifier-price]')?.value || '').trim();
                if (!name) {
                    return null;
                }
                const normalizedPrice = price === '' ? '0' : price;
                return `${name}|${normalizedPrice}`;
            })
            .filter(Boolean);

        hidden.value = lines.join('\n');
    };

    const createModifierRow = (list, modifier = { name: '', price: '0' }) => {
        const row = document.createElement('div');
        row.className = 'catalog-modifier-row';
        row.setAttribute('data-modifier-row', '');
        row.innerHTML = `
            <div class="field" style="margin-bottom:0;">
                <label>Nombre</label>
                <input type="text" value="${String(modifier.name || '').replace(/"/g, '&quot;')}" data-modifier-name>
            </div>
            <div class="field" style="margin-bottom:0;">
                <label>Precio extra</label>
                <input type="number" step="0.01" min="0" value="${String(modifier.price || '0').replace(/"/g, '&quot;')}" data-modifier-price>
            </div>
            <button class="catalog-modifier-remove" type="button" aria-label="Quitar modificador" data-modifier-remove>&times;</button>
        `;

        row.querySelectorAll('input').forEach((input) => {
            input.addEventListener('input', () => syncModifierList(list));
            input.addEventListener('change', () => syncModifierList(list));
        });

        row.querySelector('[data-modifier-remove]')?.addEventListener('click', () => {
            row.remove();
            syncModifierList(list);
        });

        list.appendChild(row);
        syncModifierList(list);
    };

    const resetModifierList = (list, seedText = '') => {
        list.innerHTML = '';
        const parsed = parseModifierText(seedText);
        if (parsed.length) {
            parsed.forEach((modifier) => createModifierRow(list, modifier));
            return;
        }

        createModifierRow(list, { name: '', price: '0' });
    };

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

    const closeTransferModal = () => {
        if (!transferModal) {
            return;
        }

        transferModal.classList.remove('is-open');
        transferModal.setAttribute('aria-hidden', 'true');
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
        modalForm.querySelector('[name="modifiers_text"]').value = row.dataset.modifiersText ? JSON.parse(row.dataset.modifiersText) : '';
        modalForm.querySelector('[name="track_inventory"]').checked = row.dataset.track === '1';
        modalForm.querySelector('[name="is_active"]').checked = row.dataset.active === '1';

        const modalModifierList = modalForm.querySelector('[data-modifier-list]');
        if (modalModifierList) {
            resetModifierList(modalModifierList, row.dataset.modifiersText ? JSON.parse(row.dataset.modifiersText) : '');
        }

        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
    };

    const openTransferModalForRow = (row) => {
        if (!transferModal || !transferForm || !row) {
            return;
        }

        const name = row.dataset.name || 'Producto sin nombre';
        const stock = Number(row.dataset.stock || '0');
        const title = transferModal.querySelector('[data-catalog-transfer-title]');
        const stockNote = transferModal.querySelector('[data-catalog-transfer-stock]');
        const quantityInput = transferForm.querySelector('[name="quantity"]');
        const destinationInput = transferForm.querySelector('[name="destination_store_id"]');

        transferForm.action = row.dataset.transferUrl || transferForm.action;

        if (title) {
            title.textContent = name;
        }
        if (stockNote) {
            stockNote.textContent = `Stock disponible en {{ $store->name }}: ${stock}`;
        }
        if (quantityInput) {
            quantityInput.removeAttribute('max');
            quantityInput.value = '';
            quantityInput.placeholder = stock > 0 ? `Disponible: ${stock}` : '0';
        }
        if (destinationInput) {
            destinationInput.value = '';
        }

        transferModal.classList.add('is-open');
        transferModal.setAttribute('aria-hidden', 'false');
    };

    const normalizeValue = (value, type) => {
        if (type === 'number') {
            const numeric = Number(value);
            return Number.isFinite(numeric) ? String(numeric) : '';
        }

        return String(value ?? '').trim();
    };

    const updateRowDirtyState = (row) => {
        if (!row) {
            return;
        }

        const nameInput = row.querySelector('[name="name"][data-catalog-inline-input]');
        const priceInput = row.querySelector('[name="price"][data-catalog-inline-input]');
        const stockInput = row.querySelector('[name="stock_on_hand"][data-catalog-inline-input]');
        const saveButton = row.querySelector('[data-catalog-inline-save]');

        if (!nameInput || !priceInput || !stockInput || !saveButton) {
            return;
        }

        const isDirty =
            normalizeValue(nameInput.value, 'text') !== normalizeValue(row.dataset.name, 'text') ||
            normalizeValue(priceInput.value, 'number') !== normalizeValue(row.dataset.price, 'number') ||
            normalizeValue(stockInput.value, 'number') !== normalizeValue(row.dataset.stock, 'number');

        saveButton.classList.toggle('is-hidden', !isDirty);
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

    modifierContainers.forEach((list) => {
        resetModifierList(list, list.dataset.modifierSeed ? JSON.parse(list.dataset.modifierSeed) : '');

        const wrapper = list.closest('.field') || list.parentElement;
        wrapper?.querySelector('[data-modifier-add]')?.addEventListener('click', () => {
            createModifierRow(list, { name: '', price: '0' });
        });
    });

    rows.forEach((row) => {
        row.querySelectorAll('[data-catalog-inline-input]').forEach((input) => {
            input.addEventListener('input', () => updateRowDirtyState(row));
            input.addEventListener('change', () => updateRowDirtyState(row));
        });

        updateRowDirtyState(row);
    });

    root.querySelectorAll('[data-catalog-edit-open]').forEach((button) => {
        button.addEventListener('click', () => {
            const row = button.closest('[data-catalog-row]');
            openModalForRow(row);
        });
    });

    root.querySelectorAll('[data-catalog-transfer-open]').forEach((button) => {
        button.addEventListener('click', () => {
            const row = button.closest('[data-catalog-row]');
            openTransferModalForRow(row);
        });
    });

    document.querySelectorAll('[data-catalog-edit-close]').forEach((button) => {
        button.addEventListener('click', closeModal);
    });

    document.querySelectorAll('[data-catalog-transfer-close]').forEach((button) => {
        button.addEventListener('click', closeTransferModal);
    });

    if (modal) {
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal();
            }
        });
    }

    if (transferModal) {
        transferModal.addEventListener('click', (event) => {
            if (event.target === transferModal) {
                closeTransferModal();
            }
        });
    }

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeModal();
            closeTransferModal();
        }
    });

    connect();
})();
</script>
@endpush
@endsection
