@extends('layouts.panel')

@section('title', 'Landings · Landora')

@section('content')
<h1>Tus landings</h1>

@if (session('ok'))
    <div class="flash">{{ session('ok') }}</div>
@endif

<div class="card">
    <h2>Nueva landing</h2>
    <form method="POST" action="{{ route('landings.store') }}" class="inline-form">
        @csrf
        <input type="text" name="name" placeholder="Nombre de la landing" value="{{ old('name') }}" required>
        <button type="submit" class="btn-sm">Crear</button>
    </form>
    @error('name')<p class="err">{{ $message }}</p>@enderror
</div>

<div class="card">
    @if ($landings->isEmpty())
        <p class="muted">Aún no tienes landings. Crea la primera con el formulario de arriba.</p>
    @else
        <table class="tbl">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Slug</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($landings as $landing)
                    <tr>
                        <td>{{ $landing->name }}</td>
                        <td><code>/{{ $landing->slug }}</code></td>
                        <td><span class="badge">{{ $landing->status }}</span></td>
                        <td class="right">
                            <form method="POST" action="{{ route('landings.destroy', $landing) }}"
                                  onsubmit="return confirm('¿Eliminar esta landing?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-del">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
