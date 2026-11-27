<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Zona de administración: gestión de usuarios. Solo accesible con rol "admin"
 * (lo controla el middleware propio EnsureUserHasRole en las rutas).
 */
class AdminController extends Controller
{
    /** Roles que existen en la aplicación. */
    private const ROLES = ['admin', 'editor'];

    /**
     * Lista de usuarios con cuántas landings y leads tiene cada uno.
     */
    public function index()
    {
        // withCount añade landings_count y leads_count. Los leads se cuentan a través
        // de las landings del usuario (relación hasManyThrough en el modelo User).
        $usuarios = User::withCount(['landings', 'leads'])->orderBy('name')->get();

        return view('admin', compact('usuarios'));
    }

    /**
     * Crea un usuario nuevo (por ejemplo, un editor para un cliente).
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'email' => ['required', 'email', 'max:191', 'unique:users,email'],
            'password' => ['required', 'string', 'min:10'],
            'role' => ['required', Rule::in(self::ROLES)],
        ], [
            'email.unique' => 'Ya existe un usuario con ese email.',
            'password.min' => 'La contraseña debe tener al menos 10 caracteres.',
        ]);

        // La contraseña se guarda cifrada automáticamente (cast "hashed" del modelo User).
        User::create($data);

        return redirect()->route('admin')->with('ok', 'Usuario creado: ' . $data['email']);
    }

    /**
     * Cambia el rol de un usuario. Nadie puede cambiarse el suyo: así el administrador
     * que está usando el panel nunca se quita el permiso a sí mismo y siempre queda
     * al menos un administrador.
     */
    public function updateRole(Request $request, User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['general' => 'No puedes cambiar tu propio rol.']);
        }

        $data = $request->validate([
            'role' => ['required', Rule::in(self::ROLES)],
        ]);

        $user->role = $data['role'];
        $user->save();

        return back()->with('ok', 'Rol de ' . $user->email . ' cambiado a ' . ($user->role === 'admin' ? 'administrador' : 'editor') . '.');
    }

    /**
     * Elimina un usuario. Por las claves foráneas con ON DELETE CASCADE, se borran
     * también sus landings y, con ellas, sus leads, visitas y envíos al webhook.
     */
    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['general' => 'No puedes eliminar tu propia cuenta.']);
        }

        $email = $user->email;
        $user->delete();

        return redirect()->route('admin')->with('ok', 'Usuario eliminado, junto con sus landings y leads: ' . $email);
    }
}
