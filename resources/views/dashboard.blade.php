@extends('layouts.app')

@section('title', 'Panel · Landora')

@section('content')
<div class="app-shell">
    <header class="topbar">
        <div class="brand">
            <span class="brand-mark">L</span>
            <span class="brand-name">Landora</span>
        </div>
        <div class="topbar-right">
            <span class="user">{{ auth()->user()->name }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn-ghost">Salir</button>
            </form>
        </div>
    </header>

    <main class="content">
        <h1>Bienvenido a Landora</h1>
        <p class="muted">Has iniciado sesión correctamente. Desde aquí gestionarás tus landing pages.</p>
    </main>
</div>
@endsection
