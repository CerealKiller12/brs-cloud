@extends('layouts.app', ['title' => 'Registro | Venpi Cloud'])

@push('head')
<style>
    .register-social-stack {
        display: grid;
        gap: 14px;
        max-width: 500px;
        margin-top: 26px;
    }
    .register-social-button {
        width: 100%;
        min-height: 54px;
        border-radius: 16px;
        font-weight: 600;
    }
    .register-social-button.google {
        background: #ffffff;
        color: #1f1f1f;
        border: 1px solid #d9dfe5;
    }
    .register-social-button.google:hover {
        background: #f7f9fb;
    }
    .register-social-button.apple {
        background: #111111;
        color: #ffffff;
        border: 1px solid #111111;
    }
    .register-social-button.apple:hover {
        background: #000000;
    }
</style>
@endpush

@section('content')
<div class="login-wrap">
    <div class="login-card" style="width: min(640px, 100%); padding: 38px 40px;">
        <div style="display: grid; gap: 10px; max-width: 500px;">
            <small class="eyebrow">Venpi</small>
            <h1 style="margin-bottom: 0;">Crea tu cuenta</h1>
            <p>Empieza con tu negocio, tu primera sucursal y el acceso inicial para comenzar a operar en Venpi.</p>
        </div>

        <div class="register-social-stack">
            <a class="button-secondary register-social-button google" href="{{ route('social.redirect', 'google') }}">Crear con Google</a>
            <a class="button-secondary register-social-button apple" href="{{ route('social.redirect', 'apple') }}">Crear con Apple</a>
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
