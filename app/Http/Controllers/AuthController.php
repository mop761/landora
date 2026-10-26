<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Muestra el formulario de acceso.
     */
    public function showLogin()
    {
        return view('auth.login');
    }

    /**
     * Procesa el intento de acceso.
     */
    public function login(Request $request)
    {
        // 1) Validar los datos recibidos.
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // 2) Intentar autenticar con esas credenciales.
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            // Regenerar la sesión evita el "session fixation".
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        // 3) Si falla, volver al formulario con un error.
        return back()
            ->withErrors(['email' => 'Las credenciales no son correctas.'])
            ->onlyInput('email');
    }

    /**
     * Cierra la sesión del usuario.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
