<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\WebhookController;

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
    // Panel principal con el resumen de analítica de todas las landings.
    Route::get('/dashboard', [AnalyticsController::class, 'dashboard'])->name('dashboard');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Gestión de landings.
    Route::get('/landings', [LandingController::class, 'index'])->name('landings.index');
    Route::post('/landings', [LandingController::class, 'store'])->name('landings.store');
    Route::get('/landings/{landing}/editor', [LandingController::class, 'edit'])->name('landings.editor');
    Route::put('/landings/{landing}/layout', [LandingController::class, 'updateLayout'])->name('landings.layout');
    Route::post('/landings/{landing}/publish', [LandingController::class, 'togglePublish'])->name('landings.publish');
    Route::get('/landings/{landing}/leads', [LandingController::class, 'leads'])->name('landings.leads');
    Route::get('/landings/{landing}/analytics', [AnalyticsController::class, 'landing'])->name('landings.analytics');
    Route::delete('/landings/{landing}', [LandingController::class, 'destroy'])->name('landings.destroy');

    // Webhook de cada landing: configuración, prueba y reintentos.
    Route::get('/landings/{landing}/webhook', [WebhookController::class, 'edit'])->name('landings.webhook');
    Route::put('/landings/{landing}/webhook', [WebhookController::class, 'update'])->name('landings.webhook.update');
    Route::post('/landings/{landing}/webhook/test', [WebhookController::class, 'test'])->name('landings.webhook.test');
    Route::post('/landings/{landing}/webhook/secret', [WebhookController::class, 'regenerateSecret'])->name('landings.webhook.secret');
    Route::post('/webhook-deliveries/{delivery}/retry', [WebhookController::class, 'retry'])->name('webhook.retry');

    // Zona de administración: solo para usuarios con rol 'admin'.
    Route::get('/admin', function () {
        return view('admin');
    })->middleware('role:admin')->name('admin');
});

// Rutas públicas de la landing (sin login), enlazadas por el slug.
// Van al final a propósito: así no interceptan rutas como /login o /dashboard.
Route::get('/{landing:slug}', [LandingController::class, 'show'])->name('landings.show');
Route::post('/{landing:slug}', [LandingController::class, 'storeLead'])->name('landings.lead');
