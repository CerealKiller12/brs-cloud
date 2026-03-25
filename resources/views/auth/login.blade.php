@extends('layouts.app', ['title' => 'Acceso | Venpi Cloud'])

@push('head')
<style>
    .auth-intro {
        display: grid;
        gap: 10px;
        max-width: 500px;
    }
    .auth-form {
        max-width: 500px;
        margin-top: 28px;
    }
    .auth-actions {
        display: grid;
        gap: 18px;
        margin-top: 22px;
    }
    .auth-primary {
        width: 100%;
        min-height: 3.25rem;
        border-radius: 16px;
        font-weight: 700;
    }
    .account-link-row {
        max-width: 500px;
        margin-top: 6px;
        font-size: 14px;
        color: var(--muted);
    }
    .social-stack {
        display: grid;
        gap: 0.8rem;
        max-width: 500px;
        margin-top: 28px;
    }
    .social-divider {
        font-size: 0.92rem;
        color: var(--muted);
        text-align: center;
    }
    .social-button {
        width: 100%;
        min-height: 3.25rem;
        padding-inline: 1.1rem 1.3rem;
        border-radius: 16px;
        font-weight: 600;
        letter-spacing: 0.01em;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.8rem;
        text-decoration: none;
        transition:
            transform 0.18s ease,
            box-shadow 0.18s ease,
            border-color 0.18s ease,
            background 0.18s ease;
    }
    .social-button.google {
        background: #ffffff;
        color: #1f1f1f;
        border: 1px solid rgba(60, 64, 67, 0.18);
        box-shadow: 0 10px 24px rgba(60, 64, 67, 0.08);
    }
    .social-button.google:hover {
        transform: translateY(-1px);
        background: #f8fbff;
        border-color: rgba(66, 133, 244, 0.34);
        box-shadow: 0 12px 26px rgba(66, 133, 244, 0.12);
    }
    .social-button.apple {
        background: #111111;
        color: #ffffff;
        border: 1px solid #111111;
        box-shadow: 0 12px 26px rgba(17, 17, 17, 0.18);
    }
    .social-button.apple:hover {
        background: #000000;
        border-color: #000000;
        box-shadow: 0 14px 28px rgba(0, 0, 0, 0.24);
    }
    .social-button__icon {
        display: inline-grid;
        place-items: center;
        width: 1.35rem;
        height: 1.35rem;
        flex: 0 0 1.35rem;
    }
    .social-button__icon svg {
        width: 100%;
        height: 100%;
        display: block;
    }
    .social-button__label {
        white-space: nowrap;
    }
    .social-hint {
        font-size: 14px;
        color: var(--muted);
    }
    .account-link-row a {
        color: var(--text);
        font-weight: 600;
        text-decoration: none;
    }
    .account-link-row a:hover {
        text-decoration: underline;
    }
</style>
@endpush

@section('content')
<div class="login-wrap">
    <div class="login-card" style="width: min(640px, 100%); padding: 38px 40px;">
        <div class="auth-intro">
            <small class="eyebrow">Venpi</small>
            <h1 style="margin-bottom: 0;">Inicia sesion</h1>
            <p>Entra con tu cuenta para administrar sucursales, cajas y catalogo desde un solo lugar.</p>
        </div>

        <form method="POST" action="{{ route('login.submit') }}" class="auth-form">
            @csrf
            <div class="field">
                <label for="email">Correo</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>
            </div>
            <div class="field">
                <label for="password">Contrasena</label>
                <input id="password" name="password" type="password" required>
            </div>
            <div class="auth-actions">
                <button class="button auth-primary" type="submit">Iniciar sesion</button>
            </div>
        </form>

        <div class="social-stack">
            <p class="social-divider">o inicia sesion con</p>
            <a class="button-secondary social-button google" href="{{ route('social.redirect', 'google') }}">
                <span class="social-button__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" focusable="false">
                        <path fill="#4285F4" d="M21.64 12.2c0-.64-.06-1.25-.16-1.84H12v3.48h5.41a4.63 4.63 0 0 1-2 3.04v2.52h3.24c1.9-1.74 2.99-4.31 2.99-7.2Z"/>
                        <path fill="#34A853" d="M12 22c2.7 0 4.96-.9 6.61-2.44l-3.24-2.52c-.9.6-2.05.96-3.37.96-2.6 0-4.8-1.76-5.58-4.12H3.07v2.6A9.99 9.99 0 0 0 12 22Z"/>
                        <path fill="#FBBC05" d="M6.42 13.88A6 6 0 0 1 6.1 12c0-.65.11-1.28.32-1.88V7.52H3.07A9.99 9.99 0 0 0 2 12c0 1.61.38 3.14 1.07 4.48l3.35-2.6Z"/>
                        <path fill="#EA4335" d="M12 5.96c1.47 0 2.8.5 3.84 1.49l2.88-2.88C16.95 2.92 14.7 2 12 2A9.99 9.99 0 0 0 3.07 7.52l3.35 2.6C7.2 7.72 9.4 5.96 12 5.96Z"/>
                    </svg>
                </span>
                <span class="social-button__label">Continuar con Google</span>
            </a>
            <a class="button-secondary social-button apple" href="{{ route('social.redirect', 'apple') }}">
                <span class="social-button__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" focusable="false">
                        <path fill="currentColor" d="M16.71 12.6c.02 2.2 1.93 2.93 1.95 2.94-.02.05-.3 1.05-1 2.08-.61.9-1.25 1.8-2.25 1.81-.98.02-1.3-.58-2.42-.58-1.12 0-1.48.56-2.4.6-.96.04-1.69-.97-2.31-1.86-1.26-1.83-2.22-5.16-.93-7.39.64-1.11 1.78-1.82 3.02-1.84.94-.02 1.82.64 2.42.64.6 0 1.71-.79 2.89-.67.5.02 1.89.2 2.78 1.5-.07.04-1.66.97-1.65 2.77Zm-2.03-4.95c.51-.62.86-1.47.77-2.33-.74.03-1.63.49-2.16 1.11-.47.55-.89 1.42-.78 2.26.83.06 1.67-.42 2.17-1.04Z"/>
                    </svg>
                </span>
                <span class="social-button__label">Continuar con Apple</span>
            </a>
            <p class="social-hint">Tambien puedes entrar con la misma cuenta de Apple o Google que usas en Venpi.</p>
        </div>

        <div class="account-link-row">
            ¿Todavia no tienes cuenta? <a href="{{ route('register') }}">Crear cuenta</a>
        </div>

        @if ($errors->any())
            <div class="error" style="max-width: 500px; margin-top: 16px;">{{ $errors->first() }}</div>
        @endif
    </div>
</div>
@endsection
