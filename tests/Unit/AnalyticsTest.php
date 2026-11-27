<?php

namespace Tests\Unit;

use App\Support\Analytics;
use PHPUnit\Framework\TestCase;

/**
 * Pruebas del cálculo de la tasa de conversión (leads / visitantes únicos × 100).
 */
class AnalyticsTest extends TestCase
{
    public function test_calcula_la_tasa_con_un_decimal(): void
    {
        $this->assertSame(5.0, Analytics::tasa(5, 100));
        $this->assertSame(33.3, Analytics::tasa(1, 3));
    }

    public function test_sin_visitantes_la_tasa_es_cero(): void
    {
        // Evita la división entre cero.
        $this->assertSame(0.0, Analytics::tasa(3, 0));
    }
}
