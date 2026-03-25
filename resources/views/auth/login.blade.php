@extends('layouts.app', ['title' => 'Acceso | Venpi Cloud'])

@section('content')
<div class="login-wrap">
    <div class="login-card" style="width: min(640px, 100%); padding: 38px 40px;">
        <div style="display: grid; gap: 10px; max-width: 500px;">
            <small class="eyebrow">Venpi</small>
            <h1 style="margin-bottom: 0;">Inicia sesion</h1>
            <p>Entra con tu cuenta para conectar tus sucursales, cajas y catalogo en un solo lugar.</p>
        </div>

        <div style="display: grid; gap: 14px; max-width: 500px; margin-top: 26px;">
            <a class="button-secondary" href="{{ route('social.redirect', 'google') }}" style="width: 100%; min-height: 54px;">Continuar con Google</a>
            <a class="button-secondary" href="{{ route('social.redirect', 'apple') }}" style="width: 100%; min-height: 54px;">Continuar con Apple</a>
            <p class="muted" style="font-size: 14px;">Si todavia no existe tu cuenta, la creamos automaticamente la primera vez que entres con Google o Apple.</p>
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

        @if ($errors->any())
            <div class="error" style="max-width: 500px;">{{ $errors->first() }}</div>
        @endif
    </div>
</div>
@endsection
