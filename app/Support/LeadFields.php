<?php

namespace App\Support;

use Illuminate\Validation\Rule;

/**
 * Validación en el servidor de los envíos del formulario de una landing.
 *
 * Los campos del formulario no son fijos: los define el usuario en el editor y quedan
 * guardados en el JSON del diseño. Esta clase lee esos campos y construye las reglas de
 * validación de Laravel según el tipo de cada campo y si es obligatorio.
 * (El navegador ya avisa con el atributo "required", pero eso se puede saltar fácilmente.)
 */
class LeadFields
{
    /**
     * Campos del formulario de la landing (cada landing tiene un único formulario).
     */
    public static function campos(?array $layout): array
    {
        foreach (($layout['sections'] ?? []) as $seccion) {
            foreach (($seccion['elements'] ?? []) as $el) {
                if (is_array($el) && ($el['type'] ?? '') === 'form' && is_array($el['fields'] ?? null)) {
                    return array_values(array_filter($el['fields'], fn ($f) => is_array($f) && ! empty($f['name'])));
                }
            }
        }

        return [];
    }

    /**
     * Reglas de validación para cada campo: ['nombre_del_campo' => [reglas...]].
     */
    public static function reglas(?array $layout): array
    {
        $reglas = [];

        foreach (self::campos($layout) as $f) {
            $base = ! empty($f['required']) ? ['required'] : ['nullable'];

            $reglas[(string) $f['name']] = match ($f['type'] ?? 'text') {
                'email' => [...$base, 'email', 'max:191'],
                'tel' => [...$base, 'string', 'max:30'],
                'select' => [...$base, Rule::in(self::opciones($f['options'] ?? ''))],
                'pais' => [...$base, Rule::in(Countries::options())],
                'checkbox' => ! empty($f['required']) ? ['accepted'] : ['nullable'],
                default => [...$base, 'string', 'max:255'],
            };
        }

        return $reglas;
    }

    /**
     * Nombre que se muestra en los mensajes de error: la etiqueta que ve el visitante
     * ("Teléfono") en lugar del nombre interno del campo ("telefono").
     */
    public static function etiquetas(?array $layout): array
    {
        $etiquetas = [];
        foreach (self::campos($layout) as $f) {
            $etiquetas[(string) $f['name']] = (string) ($f['label'] ?? $f['name']);
        }

        return $etiquetas;
    }

    /** Opciones de un desplegable: el texto del editor, una por línea. */
    private static function opciones($texto): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $texto))));
    }
}
