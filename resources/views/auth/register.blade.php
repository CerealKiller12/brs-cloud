@extends('layouts.app', ['title' => 'Registro | Venpi Cloud'])

@section('content')
<div class="login-wrap">
    <div class="login-card" style="width: min(640px, 100%); padding: 38px 40px;">
        <div style="display: grid; gap: 10px; max-width: 500px;">
            <small class="eyebrow">Venpi</small>
            <h1 style="margin-bottom: 0;">Crea tu cuenta</h1>
            <p>Empieza con tu negocio, tu primera sucursal y el acceso inicial para comenzar a operar en Venpi.</p>
        </div>

        <div style="display: grid; gap: 14px; max-width: 500px; margin-top: 26px;">
            <a class="button" href="{{ route('social.redirect', 'google') }}" style="width: 100%; min-height: 54px;">Crear con Google</a>
            <a class="button-secondary" href="{{ route('social.redirect', 'apple') }}" style="width: 100%; min-height: 54px;">Crear con Apple</a>
        </div>

        <div class="surface" style="max-width: 500px; margin-top: 26px;">
            <h4>Que pasa despues</h4>
            <p>1. Creamos tu cuenta de negocio.</p>
            <p>2. Creamos la sucursal <strong>Caja principal</strong>.</p>
            <p>3. Te dejamos como responsable inicial para administrar catalogo, cajas y sincronizacion.</p>
        </div>

        <div class="row-actions" style="justify-content: space-between; max-width: 500px; margin-top: 24px;">
            <a class="pill" href="{{ route('login') }}">Ya tengo cuenta</a>
        </div>

        @if ($errors->any())
            <div class="error" style="max-width: 500px;">{{ $errors->first() }}</div>
        @endif
    </div>
</div>
@endsection
