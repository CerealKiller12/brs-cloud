@extends('layouts.app', ['title' => 'Registro | Venpi Cloud'])

@section('content')
<div class="login-wrap">
    <div class="login-card">
        <small class="eyebrow">Registro</small>
        <h1>Crea tu espacio en Venpi Cloud</h1>
        <p>Al registrarte generamos tu cuenta de negocio, tu sucursal principal y el usuario inicial para empezar a conectar cajas.</p>

        <div style="display: grid; gap: 12px; margin-top: 28px;">
            <a class="button" href="{{ route('social.redirect', 'google') }}" style="width: 100%;">Crear con Google</a>
            <a class="button-secondary" href="{{ route('social.redirect', 'apple') }}" style="width: 100%;">Crear con Apple</a>
        </div>

        <div class="surface" style="margin-top: 24px;">
            <h4>Que pasa despues</h4>
            <p>1. Creamos tu cuenta de negocio.</p>
            <p>2. Creamos la sucursal <strong>Caja principal</strong>.</p>
            <p>3. Te dejamos como responsable inicial para administrar catalogo, cajas y sincronizacion.</p>
        </div>

        <div class="row-actions" style="justify-content: space-between; margin-top: 22px;">
            <a class="pill" href="{{ route('login') }}">Ya tengo cuenta</a>
        </div>

        @if ($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif
    </div>
</div>
@endsection
