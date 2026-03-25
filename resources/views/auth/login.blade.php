@extends('layouts.app', ['title' => 'Acceso | Venpi Cloud'])

@push('head')
<style>
    .social-stack {
        display: grid;
        gap: 14px;
        max-width: 500px;
        margin-top: 28px;
        padding-top: 24px;
        border-top: 1px solid var(--line);
    }
    .social-divider {
        font-size: 13px;
        color: var(--muted);
    }
    .social-button {
        width: 100%;
        min-height: 54px;
        border-radius: 16px;
        font-weight: 600;
    }
    .social-button.google {
        background: #ffffff;
        color: #1f1f1f;
        border: 1px solid #d9dfe5;
    }
    .social-button.google:hover {
        background: #f7f9fb;
    }
    .social-button.apple {
        background: #111111;
        color: #ffffff;
        border: 1px solid #111111;
    }
    .social-button.apple:hover {
        background: #000000;
    }
</style>
@endpush

@section('content')
<div class="login-wrap">
    <div class="login-card" style="width: min(640px, 100%); padding: 38px 40px;">
        <div style="display: grid; gap: 10px; max-width: 500px;">
            <small class="eyebrow">Venpi</small>
            <h1 style="margin-bottom: 0;">Inicia sesion</h1>
            <p>Entra con tu cuenta para conectar tus sucursales, cajas y catalogo en un solo lugar.</p>
        </div>

        <form method="POST" action="{{ route('login.submit') }}" style="max-width: 500px; margin-top: 28px; border-top: 1px solid var(--line); padding-top: 26px;">
            @csrf
            <div class="field">
                <label for="email">Correo</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>
            </div>
            <div class="field">
                <label for="password">Contrasena</label>
                <input id="password" name="password" type="password" required>
            </div>
            <div class="row-actions" style="justify-content: space-between; margin-top: 22px;">
                <a class="pill" href="{{ route('register') }}">Crear cuenta</a>
                <button class="button" type="submit">Entrar</button>
            </div>
        </form>

        <div class="social-stack">
            <p class="social-divider">O si prefieres, continua con tu cuenta de Google o Apple.</p>
            <a class="button-secondary social-button google" href="{{ route('social.redirect', 'google') }}">Continuar con Google</a>
            <a class="button-secondary social-button apple" href="{{ route('social.redirect', 'apple') }}">Continuar con Apple</a>
            <p class="muted" style="font-size: 14px;">Si todavia no existe tu cuenta, la creamos automaticamente la primera vez que entres con Google o Apple.</p>
        </div>

        @if ($errors->any())
            <div class="error" style="max-width: 500px;">{{ $errors->first() }}</div>
        @endif
    </div>
</div>
@endsection
