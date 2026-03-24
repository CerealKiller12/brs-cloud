@extends('layouts.app', ['title' => 'Acceso | BRS Cloud'])

@section('content')
<div class="login-wrap">
    <div class="login-card">
        <small class="eyebrow">Acceso</small>
        <h1>Entra a BRS Cloud</h1>
        <p>Administra sucursales, cajas y catalogo compartido desde un solo lugar.</p>

        <div style="display: grid; gap: 12px; margin-top: 24px; margin-bottom: 26px;">
            <a class="button-secondary" href="{{ route('social.redirect', 'google') }}" style="width: 100%;">Continuar con Google</a>
            <a class="button-secondary" href="{{ route('social.redirect', 'apple') }}" style="width: 100%;">Continuar con Apple</a>
            <p class="muted" style="font-size: 14px;">Si no existe cuenta, la creamos al entrar por primera vez con Google o Apple.</p>
        </div>

        <form method="POST" action="{{ route('login.submit') }}" style="margin-top: 24px; border-top: 1px solid var(--line); padding-top: 24px;">
            @csrf
            <div class="field">
                <label for="email">Correo</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>
            </div>
            <div class="field">
                <label for="password">Contrasena</label>
                <input id="password" name="password" type="password" required>
            </div>
            <div class="row-actions" style="justify-content: space-between; margin-top: 18px;">
                <a class="pill" href="{{ route('register') }}">Crear cuenta</a>
                <button class="button" type="submit">Entrar</button>
            </div>
        </form>

        @if ($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif
    </div>
</div>
@endsection
