<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Pruebas de acceso: sin iniciar sesión, el panel no es accesible y se redirige al login.
 * Hacen peticiones reales a la aplicación (rutas + middleware), sin tocar la base de datos.
 */
class AccesoTest extends TestCase
{
    public function test_la_raiz_lleva_al_panel(): void
    {
        $this->get('/')->assertRedirect(route('dashboard'));
    }

    public function test_un_invitado_no_puede_ver_el_panel(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->get('/landings')->assertRedirect(route('login'));
        $this->get('/admin')->assertRedirect(route('login'));
    }

    public function test_la_pagina_de_login_carga(): void
    {
        $this->get('/login')->assertOk();
    }
}
