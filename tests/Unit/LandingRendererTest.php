<?php

namespace Tests\Unit;

use App\Support\LandingRenderer;
use PHPUnit\Framework\TestCase;

/**
 * Pruebas del renderizador: convierte el diseño (JSON) en HTML sin dejar pasar código peligroso.
 */
class LandingRendererTest extends TestCase
{
    /** Diseño mínimo de una sección con los elementos indicados. */
    private function disenoCon(array $elementos): array
    {
        return ['sections' => [['cols' => 1, 'elements' => $elementos]]];
    }

    public function test_escapa_el_html_de_los_titulares(): void
    {
        $html = LandingRenderer::render($this->disenoCon([
            ['type' => 'heading', 'text' => '<script>alert(1)</script>'],
        ]));

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_neutraliza_los_enlaces_javascript(): void
    {
        $html = LandingRenderer::render($this->disenoCon([
            ['type' => 'button', 'label' => 'Pulsa', 'href' => 'javascript:alert(1)'],
            ['type' => 'text', 'text' => 'Texto', 'link' => 'javascript:alert(1)'],
        ]));

        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringContainsString('href="#"', $html);
    }

    public function test_un_color_no_valido_se_sustituye(): void
    {
        $html = LandingRenderer::render($this->disenoCon([
            ['type' => 'heading', 'text' => 'Hola', 'color' => 'red;background:url(x)'],
        ]));

        $this->assertStringNotContainsString('url(x)', $html);
        $this->assertStringContainsString('color:#000000', $html);
    }

    public function test_un_diseno_vacio_no_rompe_la_pagina(): void
    {
        $this->assertStringContainsString('todavía no tiene contenido', LandingRenderer::render(null));
        $this->assertStringContainsString('todavía no tiene contenido', LandingRenderer::render([]));
    }

    public function test_el_campo_pais_muestra_la_lista_de_paises(): void
    {
        $html = LandingRenderer::render($this->disenoCon([
            ['type' => 'form', 'fields' => [['label' => 'País', 'name' => 'pais', 'type' => 'pais']]],
        ]));

        $this->assertStringContainsString('<select id="campo-pais" name="pais"', $html);
        // La etiqueta está asociada al campo (accesibilidad).
        $this->assertStringContainsString('<label for="campo-pais"', $html);
        $this->assertStringContainsString('España (+34)', $html);
    }

    public function test_el_formulario_lleva_el_token_csrf_y_las_utm(): void
    {
        $html = LandingRenderer::render(
            $this->disenoCon([['type' => 'form', 'fields' => [['label' => 'Email', 'name' => 'email', 'type' => 'email']]]]),
            ['action' => '/mi-landing', 'csrf' => 'TOKEN123', 'utm' => ['source' => 'google']]
        );

        $this->assertStringContainsString('name="_token" value="TOKEN123"', $html);
        $this->assertStringContainsString('name="_utm_source" value="google"', $html);
        // Lleva el campo trampa antispam (honeypot).
        $this->assertStringContainsString('name="_web"', $html);
    }
}
