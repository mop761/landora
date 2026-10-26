<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Limita la longitud por defecto de las cadenas a 191 caracteres.
        // Evita el error "key was too long" en MySQL/MariaDB con utf8mb4.
        Schema::defaultStringLength(191);
    }
}
