@extends('layouts.app', ['title' => 'Catalogo | BRS Cloud'])

@section('content')
<section class="hero">
    <small>Catalogo cloud</small>
    <h2>Productos compartidos</h2>
    <p>Este snapshot alimenta cajas offline-first y se redistribuye por version de catalogo.</p>
</section>

@if (session('status'))
    <section class="card" style="padding: 16px 20px; background: #edf7ef; border-color: #c9e6cf; color: #24523a;">
        {{ session('status') }}
    </section>
@endif

@if ($errors->any())
    <section class="card" style="padding: 16px 20px; background: #fde8e3; border-color: #f6c9bf; color: #9d4635;">
        {{ $errors->first() }}
    </section>
@endif

<section class="grid grid-2">
    <article class="card">
        <div class="toolbar">
            <div>
                <small class="eyebrow">Editor</small>
                <h3>{{ $editProduct ? 'Editar producto cloud' : 'Nuevo producto cloud' }}</h3>
            </div>
            @if ($editProduct)
                <a class="pill" href="{{ route('catalog.index') }}">Cancelar edicion</a>
            @endif
        </div>

        <form method="POST" action="{{ $editProduct ? route('catalog.update', $editProduct->id) : route('catalog.store') }}" class="grid grid-2">
            @csrf
            @if ($editProduct)
                @method('PUT')
            @endif

            <div>
                <label for="sku">SKU</label>
                <input id="sku" name="sku" value="{{ old('sku', $editProduct->sku ?? '') }}" required>
            </div>
            <div>
                <label for="barcode">Codigo de barras</label>
                <input id="barcode" name="barcode" value="{{ old('barcode', $editProduct->barcode ?? '') }}">
            </div>
            <div style="grid-column: 1 / -1;">
                <label for="name">Nombre</label>
                <input id="name" name="name" value="{{ old('name', $editProduct->name ?? '') }}" required>
            </div>
            <div>
                <label for="price">Precio</label>
                <input id="price" name="price" type="number" step="0.01" min="0" value="{{ old('price', isset($editProduct) ? number_format($editProduct->price_cents / 100, 2, '.', '') : '') }}" required>
            </div>
            <div>
                <label for="cost">Costo</label>
                <input id="cost" name="cost" type="number" step="0.01" min="0" value="{{ old('cost', isset($editProduct) ? number_format($editProduct->cost_cents / 100, 2, '.', '') : '') }}">
            </div>
            <div>
                <label for="stock_on_hand">Stock</label>
                <input id="stock_on_hand" name="stock_on_hand" type="number" min="0" value="{{ old('stock_on_hand', $editProduct->stock_on_hand ?? 0) }}" required>
            </div>
            <div>
                <label for="reorder_point">Punto de reorden</label>
                <input id="reorder_point" name="reorder_point" type="number" min="0" value="{{ old('reorder_point', $editProduct->reorder_point ?? 0) }}" required>
            </div>
            <div style="grid-column: 1 / -1; display: flex; align-items: center; gap: 10px;">
                <input id="track_inventory" name="track_inventory" type="checkbox" value="1" {{ old('track_inventory', $editProduct->track_inventory ?? true) ? 'checked' : '' }} style="width: auto;">
                <label for="track_inventory" style="margin: 0;">Controlar inventario desde cloud</label>
            </div>
            <div style="grid-column: 1 / -1; display: flex; justify-content: flex-end;">
                <button class="button" type="submit">{{ $editProduct ? 'Guardar cambios' : 'Crear producto' }}</button>
            </div>
        </form>
    </article>

    <article class="card">
        <small class="eyebrow">Store</small>
        <h3>{{ $store->name }}</h3>
        <div class="meta-list" style="margin-top: 16px;">
            <div class="meta-row"><span class="muted">Codigo</span><strong>{{ $store->code }}</strong></div>
            <div class="meta-row"><span class="muted">Catalog version</span><strong>v{{ $store->catalog_version }}</strong></div>
            <div class="meta-row"><span class="muted">Timezone</span><strong>{{ $store->timezone }}</strong></div>
            <div class="meta-row"><span class="muted">Branding</span><strong>{{ data_get($store->branding_json, 'business_name', 'n/a') }}</strong></div>
        </div>
    </article>
</section>

<section
    class="card"
    data-cloud-catalog-live
    data-catalog-version="{{ (int) $store->catalog_version }}"
    data-store-id="{{ (int) $store->id }}"
    data-reverb-app-key="{{ config('broadcasting.connections.reverb.key') }}"
    data-reverb-host="{{ env('REVERB_HOST', request()->getHost()) }}"
    data-reverb-port="{{ (int) env('REVERB_PORT', request()->isSecure() ? 443 : 80) }}"
    data-reverb-scheme="{{ env('REVERB_SCHEME', request()->isSecure() ? 'https' : 'http') }}"
    data-reverb-path="{{ env('REVERB_PATH', '') }}"
    data-reverb-channel="catalog.store.{{ (int) $store->id }}"
    data-reverb-event="catalog.version.changed">
    <div class="toolbar">
        <div>
            <small class="eyebrow">Inventario cloud</small>
            <h3>Snapshot actual</h3>
            <p class="muted" data-live-status style="margin-top: 6px;">Actualizacion automatica activa.</p>
        </div>
        <form method="GET" action="{{ route('catalog.index') }}" style="display: flex; gap: 10px; align-items: center;">
            <input name="q" value="{{ $search }}" placeholder="Buscar por nombre, SKU o barcode" style="width: 320px;">
            <button class="button" type="submit">Buscar</button>
        </form>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Producto</th>
                <th>SKU</th>
                <th>Barcode</th>
                <th>Precio</th>
                <th>Stock</th>
                <th>Estado</th>
                <th>Version</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($catalog as $item)
                <tr>
                    <td><strong>{{ $item->name }}</strong></td>
                    <td>{{ $item->sku }}</td>
                    <td>{{ $item->barcode ?: '—' }}</td>
                    <td>MX${{ number_format($item->price_cents / 100, 2) }}</td>
                    <td>{{ $item->stock_on_hand }}</td>
                    <td><span class="pill">{{ $item->is_active ? 'Activo' : 'Inactivo' }}</span></td>
                    <td>v{{ $item->catalog_version }}</td>
                    <td>
                        <div style="display: flex; gap: 10px; justify-content: flex-end; align-items: center;">
                            <a class="pill" href="{{ route('catalog.index', ['edit' => $item->id] + ($search ? ['q' => $search] : [])) }}">Editar</a>
                            <form method="POST" action="{{ route('catalog.toggle', $item->id) }}">
                                @csrf
                                <button class="pill" type="submit" style="cursor: pointer;">{{ $item->is_active ? 'Desactivar' : 'Activar' }}</button>
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
                    <td colspan="8"><div class="empty">No hay productos que coincidan con la busqueda actual.</div></td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="pagination">{{ $catalog->links() }}</div>
</section>

<script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>
<script>
(() => {
    const root = document.querySelector('[data-cloud-catalog-live]');

    if (!root || !window.Pusher) {
        return;
    }

    let currentVersion = Number(root.dataset.catalogVersion || '0');
    const appKey = root.dataset.reverbAppKey || '';
    const host = root.dataset.reverbHost || window.location.hostname;
    const port = Number(root.dataset.reverbPort || (window.location.protocol === 'https:' ? 443 : 80));
    const scheme = root.dataset.reverbScheme || (window.location.protocol === 'https:' ? 'https' : 'http');
    const path = root.dataset.reverbPath || '';
    const channelName = root.dataset.reverbChannel || '';
    const eventName = root.dataset.reverbEvent || 'catalog.version.changed';
    const liveStatus = root.querySelector('[data-live-status]');

    const setStatus = (text) => {
        if (liveStatus) {
            liveStatus.textContent = text;
        }
    };

    if (!appKey || !channelName) {
        setStatus('Actualizacion automatica no disponible en este snapshot.');
        return;
    }

    const reverb = new window.Pusher(appKey, {
        cluster: 'mt1',
        wsHost: host,
        wsPort: port,
        wssPort: port,
        wsPath: path || undefined,
        forceTLS: scheme === 'https',
        enabledTransports: ['ws', 'wss'],
        disableStats: true,
    });

    const channel = reverb.subscribe(channelName);
    setStatus(`Escuchando cambios del snapshot v${currentVersion}...`);

    channel.bind(eventName, (payload) => {
        const nextVersion = Number(payload?.catalogVersion || 0);

        if (nextVersion > currentVersion) {
            setStatus(`Aplicando cambios del snapshot v${nextVersion}...`);
            window.location.reload();
            return;
        }

        currentVersion = nextVersion;
        setStatus(`Snapshot al dia en v${currentVersion}.`);
    });

    reverb.connection.bind('connected', () => {
        setStatus(`Snapshot al dia en v${currentVersion}.`);
    });

    reverb.connection.bind('error', () => {
        setStatus('No pude conectar el snapshot cloud en tiempo real.');
    });

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            setStatus(`Escuchando cambios del snapshot v${currentVersion}...`);
        }
    });

    window.addEventListener('beforeunload', () => {
        try {
            channel.unbind_all();
            reverb.unsubscribe(channelName);
            reverb.disconnect();
        } catch {
            setStatus('Actualizacion automatica no disponible en este navegador.');
        }
    });
})();
</script>
@endsection
