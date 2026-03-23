@extends('layouts.app', ['title' => 'Login | BRS Cloud'])

@section('content')
<div class="login-wrap">
    <div class="login-card">
        <small class="eyebrow">Acceso cloud</small>
        <h1>Entra a BRS Cloud</h1>
        <p>Administra tenant, stores, devices y catalogo compartido desde una sola consola.</p>

        <form method="POST" action="{{ route('login.submit') }}" style="margin-top: 24px;">
            @csrf
            <div class="field">
                <label for="email">Correo</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" required>
            </div>
            <button class="button" type="submit">Entrar</button>
        </form>

        @if ($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif
    </div>
</div>
@endsection
