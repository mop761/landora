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
                    <th>Leads</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($landings as $landing)
                    <tr>
                        <td>{{ $landing->name }}</td>
                        <td><code>/{{ $landing->slug }}</code></td>
                        <td>
                            @if ($landing->status === 'published')
                                <span class="badge" style="background:#dcfce7;color:#166534">Publicada</span>
                            @else
                                <span class="badge" style="background:#f1f5f9;color:#64748b">Borrador</span>
                            @endif
                        </td>
                        <td>
                            <a class="btn-ghost" href="{{ route('landings.leads', $landing) }}">{{ $landing->leads_count }}</a>
                        </td>
                        <td class="right">
                            <div style="display:inline-flex;gap:8px;align-items:center">
                                @if ($landing->status === 'published')
                                    <a class="btn-ghost" href="{{ route('landings.show', $landing->slug) }}" target="_blank">Ver</a>
                                @endif
                                <a class="btn-ghost" href="{{ route('landings.editor', $landing) }}">Editar</a>
                                <form method="POST" action="{{ route('landings.publish', $landing) }}">
                                    @csrf
                                    <button type="submit" class="btn-sm">{{ $landing->status === 'published' ? 'Despublicar' : 'Publicar' }}</button>
                                </form>
                                <form method="POST" action="{{ route('landings.destroy', $landing) }}"
                                      onsubmit="return confirm('¿Eliminar esta landing?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-del">Eliminar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
