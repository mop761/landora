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
        <p class="muted">
            Has iniciado sesión como <strong>{{ auth()->user()->name }}</strong>
            — rol: <strong>{{ auth()->user()->isAdmin() ? 'Administrador' : 'Editor' }}</strong>.
        </p>

        @if (auth()->user()->isAdmin())
            <a class="link-btn" href="{{ route('admin') }}">Ir a la zona de administración</a>
        @endif
    </main>
</div>
@endsection
