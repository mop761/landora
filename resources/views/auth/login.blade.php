@extends('layouts.app')

@section('title', 'Acceder · Landora')

@section('content')
<div class="auth-wrap">
    <div class="auth-card">
        <div class="brand">
            <span class="brand-mark">L</span>
            <span class="brand-name">Landora</span>
        </div>
        <p class="auth-sub">Accede a tu panel</p>

        @if ($errors->any())
            <div class="alert">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <label for="email">Correo electrónico</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>

            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" required>

            <label class="check">
                <input type="checkbox" name="remember"> Recordarme
            </label>

            <button type="submit" class="btn">Entrar</button>
        </form>
    </div>
</div>
@endsection
