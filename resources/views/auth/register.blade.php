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
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.8rem;
        text-decoration: none;
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
    .register-social-button__icon {
        display: inline-grid;
        place-items: center;
        width: 1.35rem;
        height: 1.35rem;
        flex: 0 0 1.35rem;
    }
    .register-social-button__icon svg {
        width: 100%;
        height: 100%;
        display: block;
    }
    .register-social-button__label {
        white-space: nowrap;
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
            <a class="button-secondary register-social-button google" href="{{ route('social.redirect', 'google') }}">
                <span class="register-social-button__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" focusable="false">
                        <path fill="#4285F4" d="M21.64 12.2c0-.64-.06-1.25-.16-1.84H12v3.48h5.41a4.63 4.63 0 0 1-2 3.04v2.52h3.24c1.9-1.74 2.99-4.31 2.99-7.2Z"/>
                        <path fill="#34A853" d="M12 22c2.7 0 4.96-.9 6.61-2.44l-3.24-2.52c-.9.6-2.05.96-3.37.96-2.6 0-4.8-1.76-5.58-4.12H3.07v2.6A9.99 9.99 0 0 0 12 22Z"/>
                        <path fill="#FBBC05" d="M6.42 13.88A6 6 0 0 1 6.1 12c0-.65.11-1.28.32-1.88V7.52H3.07A9.99 9.99 0 0 0 2 12c0 1.61.38 3.14 1.07 4.48l3.35-2.6Z"/>
                        <path fill="#EA4335" d="M12 5.96c1.47 0 2.8.5 3.84 1.49l2.88-2.88C16.95 2.92 14.7 2 12 2A9.99 9.99 0 0 0 3.07 7.52l3.35 2.6C7.2 7.72 9.4 5.96 12 5.96Z"/>
                    </svg>
                </span>
                <span class="register-social-button__label">Crear con Google</span>
            </a>
            <a class="button-secondary register-social-button apple" href="{{ route('social.redirect', 'apple') }}">
                <span class="register-social-button__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" focusable="false">
                        <path fill="currentColor" d="M16.71 12.6c.02 2.2 1.93 2.93 1.95 2.94-.02.05-.3 1.05-1 2.08-.61.9-1.25 1.8-2.25 1.81-.98.02-1.3-.58-2.42-.58-1.12 0-1.48.56-2.4.6-.96.04-1.69-.97-2.31-1.86-1.26-1.83-2.22-5.16-.93-7.39.64-1.11 1.78-1.82 3.02-1.84.94-.02 1.82.64 2.42.64.6 0 1.71-.79 2.89-.67.5.02 1.89.2 2.78 1.5-.07.04-1.66.97-1.65 2.77Zm-2.03-4.95c.51-.62.86-1.47.77-2.33-.74.03-1.63.49-2.16 1.11-.47.55-.89 1.42-.78 2.26.83.06 1.67-.42 2.17-1.04Z"/>
                    </svg>
                </span>
                <span class="register-social-button__label">Crear con Apple</span>
            </a>
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
