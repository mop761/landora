<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Landora')</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <div class="brand">
            <span class="brand-mark">L</span>
            <span class="brand-name">Landora</span>
        </div>
        <nav class="nav">
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">Panel</a>
            <a href="{{ route('landings.index') }}" class="{{ request()->routeIs('landings.*') ? 'active' : '' }}">Landings</a>
            @if (auth()->user()->isAdmin())
                <a href="{{ route('admin') }}" class="{{ request()->routeIs('admin') ? 'active' : '' }}">Administración</a>
            @endif
        </nav>
    </aside>

    <div class="main">
        <header class="topbar">
            <div></div>
            <div class="topbar-right">
                <span class="user">{{ auth()->user()->name }} · {{ auth()->user()->isAdmin() ? 'Admin' : 'Editor' }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn-ghost">Salir</button>
                </form>
            </div>
        </header>

        <main class="content">
            @yield('content')
        </main>
    </div>
</div>
</body>
</html>
