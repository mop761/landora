<?php

namespace Tests\Unit;

use App\Support\LeadFields;
use Illuminate\Validation\Rules\In;
use PHPUnit\Framework\TestCase;

/**
 * Pruebas de las reglas de validación de leads, que se construyen a partir de los
 * campos del formulario definidos en el editor (JSON del diseño).
 */
class LeadFieldsTest extends TestCase
{
    private function disenoConCampos(array $campos): array
    {
        return ['sections' => [['elements' => [['type' => 'form', 'fields' => $campos]]]]];
    }

    public function test_cada_tipo_de_campo_tiene_sus_reglas(): void
    {
        $reglas = LeadFields::reglas($this->disenoConCampos([
            ['label' => 'Email', 'name' => 'email', 'type' => 'email', 'required' => true],
            ['label' => 'Teléfono', 'name' => 'telefono', 'type' => 'tel'],
            ['label' => 'Privacidad', 'name' => 'privacidad', 'type' => 'checkbox', 'required' => true],
        ]));

        $this->assertSame(['required', 'email', 'max:191'], $reglas['email']);
        $this->assertSame(['nullable', 'string', 'max:30'], $reglas['telefono']);
        $this->assertSame(['accepted'], $reglas['privacidad']);
    }

    public function test_los_desplegables_solo_admiten_sus_opciones(): void
    {
        $reglas = LeadFields::reglas($this->disenoConCampos([
            ['label' => 'Máster', 'name' => 'master', 'type' => 'select', 'options' => "Marketing\nModa"],
        ]));

        $this->assertInstanceOf(In::class, $reglas['master'][1]);
    }

    public function test_sin_formulario_no_hay_reglas(): void
    {
        $this->assertSame([], LeadFields::reglas(null));
        $this->assertSame([], LeadFields::reglas(['sections' => [['elements' => [['type' => 'heading']]]]]));
    }

    public function test_los_mensajes_usan_la_etiqueta_visible(): void
    {
        $etiquetas = LeadFields::etiquetas($this->disenoConCampos([
            ['label' => 'Teléfono', 'name' => 'telefono', 'type' => 'tel'],
        ]));

        $this->assertSame(['telefono' => 'Teléfono'], $etiquetas);
    }
}
