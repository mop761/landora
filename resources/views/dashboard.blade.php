@extends('layouts.panel')

@section('title', 'Panel · Landora')

@section('content')
<h1>Bienvenido a Landora</h1>
<p class="muted">
    Has iniciado sesión como <strong>{{ auth()->user()->name }}</strong>
    (rol: {{ auth()->user()->isAdmin() ? 'Administrador' : 'Editor' }}).
</p>
<p class="muted">
    Usa el menú de la izquierda para gestionar tus
    <a href="{{ route('landings.index') }}">landings</a>.
</p>
@endsection
