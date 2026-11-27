@extends('layouts.panel')

@section('title', 'Administración · Landora')

@section('content')
<h1>Administración</h1>
<p class="muted">Gestión de usuarios. Solo los administradores pueden ver esta sección.</p>

@if (session('ok'))
    <div class="flash">{{ session('ok') }}</div>
@endif
@error('general')
    <div class="alert">{{ $message }}</div>
@enderror

<div class="card">
    <h2>Nuevo usuario</h2>
    <form method="POST" action="{{ route('admin.users.store') }}" class="form-grid">
        @csrf
        <div>
            <label for="name">Nombre</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" maxlength="191" required>
        </div>
        <div>
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" maxlength="191" required>
        </div>
        <div>
            <label for="password">Contraseña (mín. 10)</label>
            <input id="password" type="password" name="password" minlength="10" required autocomplete="new-password">
        </div>
        <div>
            <label for="role">Rol</label>
            <select id="role" name="role">
                <option value="editor" @selected(old('role', 'editor') === 'editor')>Editor</option>
                <option value="admin" @selected(old('role') === 'admin')>Administrador</option>
            </select>
        </div>
        <button type="submit" class="btn-sm">Crear</button>
    </form>
    @if ($errors->hasAny(['name', 'email', 'password', 'role']))
        <p class="err">{{ $errors->first('name') ?: $errors->first('email') ?: $errors->first('password') ?: $errors->first('role') }}</p>
    @endif
</div>

<div class="card">
    <h2>Usuarios ({{ $usuarios->count() }})</h2>
    <table class="tbl">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Email</th>
                <th>Rol</th>
                <th class="right">Landings</th>
                <th class="right">Leads</th>
                <th>Alta</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($usuarios as $u)
                @php $soyYo = $u->id === auth()->id(); @endphp
                <tr>
                    <td>{{ $u->name }}@if ($soyYo)<span class="yo">Tú</span>@endif</td>
                    <td>{{ $u->email }}</td>
                    <td>
                        @if ($soyYo)
                            {{-- No se puede cambiar el rol propio --}}
                            {{ $u->isAdmin() ? 'Administrador' : 'Editor' }}
                        @else
                            <form method="POST" action="{{ route('admin.users.role', $u) }}" class="role-form">
                                @csrf
                                @method('PUT')
                                <select name="role" aria-label="Rol de {{ $u->name }}">
                                    <option value="editor" @selected($u->role === 'editor')>Editor</option>
                                    <option value="admin" @selected($u->role === 'admin')>Administrador</option>
                                </select>
                                <button type="submit" class="btn-ghost">Guardar</button>
                            </form>
                        @endif
                    </td>
                    <td class="right">{{ $u->landings_count }}</td>
                    <td class="right">{{ $u->leads_count }}</td>
                    <td>{{ $u->created_at?->format('d/m/Y') }}</td>
                    <td class="right">
                        @unless ($soyYo)
                            <form method="POST" action="{{ route('admin.users.destroy', $u) }}"
                                  onsubmit="return confirm('Se eliminará ' + @js($u->email) + ' junto con TODAS sus landings y leads. ¿Continuar?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-del">Eliminar</button>
                            </form>
                        @endunless
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
