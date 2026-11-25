@extends('layouts.panel')

@section('title', 'Ajustes · ' . $landing->name . ' · Landora')

@section('content')
<h1>Ajustes de «{{ $landing->name }}»</h1>

<p><a class="btn-ghost" href="{{ route('landings.index') }}">&larr; Volver a landings</a></p>

@if (session('ok'))
    <div class="flash">{{ session('ok') }}</div>
@endif

<div class="card">
    <form method="POST" action="{{ route('landings.settings.update', $landing) }}" class="form-stack">
        @csrf
        @method('PUT')

        <label for="name">Nombre</label>
        <input id="name" type="text" name="name" value="{{ old('name', $landing->name) }}" maxlength="191" required>
        @error('name')<p class="err">{{ $message }}</p>@enderror

        <label for="slug">Slug (dirección pública)</label>
        <div class="slug-field">
            <span>{{ url('/') }}/</span>
            <input id="slug" type="text" name="slug" value="{{ old('slug', $landing->slug) }}" maxlength="191" required>
        </div>
        <p class="hint-sm">Solo minúsculas, números y guiones. Los espacios y las tildes se convierten solos
            (por ejemplo, «Máster Moda» pasa a ser «master-moda»).</p>
        @error('slug')<p class="err">{{ $message }}</p>@enderror

        @if ($landing->status === 'published')
            <p class="warn">Esta landing está publicada. Si cambias el slug, la dirección antigua dejará de
                funcionar y tendrás que actualizar los enlaces de tus campañas.</p>
        @endif

        <button type="submit" class="btn-sm">Guardar cambios</button>
    </form>
</div>
@endsection
