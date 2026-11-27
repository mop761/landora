<?php

namespace Tests\Unit;

use App\Support\Slugs;
use PHPUnit\Framework\TestCase;

/**
 * Pruebas de las reglas del slug (la parte de la URL pública de cada landing).
 */
class SlugsTest extends TestCase
{
    public function test_las_rutas_del_sistema_estan_reservadas(): void
    {
        $this->assertTrue(Slugs::esReservado('login'));
        $this->assertTrue(Slugs::esReservado('dashboard'));
        $this->assertFalse(Slugs::esReservado('master-moda'));
    }

    public function test_formato_valido(): void
    {
        $this->assertMatchesRegularExpression(Slugs::FORMATO, 'master-moda');
        $this->assertMatchesRegularExpression(Slugs::FORMATO, 'black-friday-2026');
    }

    public function test_formato_no_valido(): void
    {
        foreach (['Master', 'master--moda', '-master', 'master_moda', ''] as $slug) {
            $this->assertDoesNotMatchRegularExpression(Slugs::FORMATO, $slug, "No debería aceptar '$slug'");
        }
    }
}
