@extends('layouts.app', ['title' => 'Devices | BRS Cloud'])

@section('content')
<section class="hero">
    <small>Devices</small>
    <h2>Cajas y dispositivos</h2>
    <p>Monitorea ultima conexion, plataforma, version y store asignada.</p>
</section>

<section class="card">
    <table class="table">
        <thead>
            <tr>
                <th>Device</th>
                <th>Plataforma</th>
                <th>Modo</th>
                <th>Version</th>
                <th>Ultima conexion</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($devices as $device)
                <tr>
                    <td>
                        <strong>{{ $device->name ?: $device->device_id }}</strong><br>
                        <span class="muted">{{ $device->device_id }}</span>
                    </td>
                    <td>{{ $device->platform }}</td>
                    <td>{{ $device->app_mode ?: 'sin modo' }}</td>
                    <td>{{ $device->current_version ?: 'n/a' }}</td>
                    <td>{{ optional($device->last_seen_at)->format('M j, Y · g:i A') ?: 'sin check-in' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="pagination">{{ $devices->links() }}</div>
</section>
@endsection
