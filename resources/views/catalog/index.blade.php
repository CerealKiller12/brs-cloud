@extends('layouts.app', ['title' => 'Catalogo | BRS Cloud'])

@section('content')
<section class="hero">
    <small>Catalogo cloud</small>
    <h2>Productos compartidos</h2>
    <p>Este snapshot alimenta cajas offline-first y se redistribuye por version de catalogo.</p>
</section>

<section class="card">
    <table class="table">
        <thead>
            <tr>
                <th>Producto</th>
                <th>SKU</th>
                <th>Barcode</th>
                <th>Precio</th>
                <th>Stock</th>
                <th>Version</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($catalog as $item)
                <tr>
                    <td><strong>{{ $item->name }}</strong></td>
                    <td>{{ $item->sku }}</td>
                    <td>{{ $item->barcode }}</td>
                    <td>MX${{ number_format($item->price_cents / 100, 2) }}</td>
                    <td>{{ $item->stock_on_hand }}</td>
                    <td>v{{ $item->catalog_version }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="pagination">{{ $catalog->links() }}</div>
</section>
@endsection
