<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| Rutas web de Landora
|--------------------------------------------------------------------------
*/

// La raíz lleva al panel; si no hay sesión, el middleware 'auth' redirige al login.
Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Rutas solo para invitados (sin sesión iniciada).
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Rutas solo para usuarios autenticados.
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Zona de administración: solo para usuarios con rol 'admin'.
    Route::get('/admin', function () {
        return view('admin');
    })->middleware('role:admin')->name('admin');
});
