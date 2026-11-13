<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LandingController;

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

    // Gestión de landings.
    Route::get('/landings', [LandingController::class, 'index'])->name('landings.index');
    Route::post('/landings', [LandingController::class, 'store'])->name('landings.store');
    Route::get('/landings/{landing}/editor', [LandingController::class, 'edit'])->name('landings.editor');
    Route::put('/landings/{landing}/layout', [LandingController::class, 'updateLayout'])->name('landings.layout');
    Route::delete('/landings/{landing}', [LandingController::class, 'destroy'])->name('landings.destroy');

    // Zona de administración: solo para usuarios con rol 'admin'.
    Route::get('/admin', function () {
        return view('admin');
    })->middleware('role:admin')->name('admin');
});

// Página pública de la landing (sin login). Se enlaza por el slug.
// Va al final a propósito: así no intercepta rutas como /login o /dashboard,
// que están definidas antes y tienen prioridad.
Route::get('/{landing:slug}', [LandingController::class, 'show'])->name('landings.show');
