@extends('layouts.app', ['title' => 'Stores | BRS Cloud'])

@section('content')
<section class="hero">
    <small>Stores</small>
    <h2>Sucursales del tenant</h2>
    <p>Visualiza codigo, timezone y estado de cada store conectada al cloud.</p>
</section>

<section class="card">
    <table class="table">
        <thead>
            <tr>
                <th>Store</th>
                <th>Codigo</th>
                <th>Timezone</th>
                <th>Catalogo</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($stores as $store)
                <tr>
                    <td>
                        <strong>{{ $store->name }}</strong><br>
                        <span class="muted">Tenant #{{ $store->tenant_id }}</span>
                    </td>
                    <td>{{ $store->code }}</td>
                    <td>{{ $store->timezone }}</td>
                    <td>v{{ $store->catalog_version }}</td>
                    <td><span class="pill">{{ $store->is_active ? 'Activa' : 'Inactiva' }}</span></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</section>
@endsection
