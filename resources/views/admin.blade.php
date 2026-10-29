@extends('layouts.app')

@section('title', 'Administración · Landora')

@section('content')
<div class="app-shell">
    <header class="topbar">
        <div class="brand">
            <span class="brand-mark">L</span>
            <span class="brand-name">Landora</span>
        </div>
        <div class="topbar-right">
            <a class="btn-ghost" href="{{ route('dashboard') }}">&larr; Volver al panel</a>
            <span class="user">{{ auth()->user()->name }}</span>
        </div>
    </header>

    <main class="content">
        <h1>Zona de administración</h1>
        <p class="muted">
            Esta sección solo es accesible para usuarios con rol <strong>administrador</strong>.
            Aquí irá, más adelante, la gestión de usuarios.
        </p>
    </main>
</div>
@endsection
