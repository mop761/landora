<?php

namespace App\Support;

/**
 * Convierte el diseño de una landing (el árbol JSON que guarda el editor)
 * en el HTML y el CSS que verá el visitante en la página pública.
 *
 * El editor visual (public/js/editor.js) construye este mismo árbol de
 * objetos en el navegador. Aquí lo recorremos en el servidor para generar
 * la página final. Esta "serialización" del diseño a HTML es lógica propia
 * del proyecto: no la resuelve Laravel, la escribimos nosotros.
 *
 * Estructura del árbol:
 *   [
 *     'customCss' => '...',
 *     'sections'  => [
 *        [ 'dir','bg','padY','gap','align','max', 'elements' => [ ... ] ],
 *     ],
 *   ]
 */
class LandingRenderer
{
    /**
     * Devuelve el HTML de todas las secciones de la landing.
     */
    public static function render(?array $layout): string
    {
        if (empty($layout['sections']) || ! is_array($layout['sections'])) {
            return '<p style="text-align:center;color:#64748b;padding:60px 24px">'
                . 'Esta landing todavía no tiene contenido.</p>';
        }

        $html = '';
        foreach ($layout['sections'] as $section) {
            if (is_array($section)) {
                $html .= self::renderSection($section);
            }
        }

        return $html;
    }

    /**
     * Devuelve el CSS personalizado que el usuario haya escrito para la landing.
     */
    public static function customCss(?array $layout): string
    {
        return isset($layout['customCss']) ? (string) $layout['customCss'] : '';
    }

    /* ------------------------------------------------------------------ */
    /*  Secciones                                                         */
    /* ------------------------------------------------------------------ */

    private static function renderSection(array $s): string
    {
        $dir   = ($s['dir'] ?? 'column') === 'row' ? 'row' : 'column';
        $bg    = self::color($s['bg'] ?? '#ffffff');
        $padY  = self::num($s['padY'] ?? 44, 0, 200);
        $gap   = self::num($s['gap'] ?? 16, 0, 100);
        $align = self::align($s['align'] ?? 'center');

        // Alineación transversal (la misma lógica que aplica el editor).
        if ($dir === 'row') {
            $items = 'center';
        } else {
            $items = $align === 'center' ? 'center' : ($align === 'right' ? 'flex-end' : 'flex-start');
        }

        $style = 'display:flex;gap:' . $gap . 'px;background:' . $bg . ';'
            . 'padding:' . $padY . 'px 40px;align-items:' . $items . ';';

        if ($dir === 'row') {
            $style .= 'flex-direction:row;flex-wrap:wrap;justify-content:center;';
        } else {
            $max    = self::num($s['max'] ?? 640, 240, 1600);
            $style .= 'flex-direction:column;max-width:' . $max . 'px;margin-left:auto;margin-right:auto;';
        }

        $inner = '';
        foreach (($s['elements'] ?? []) as $el) {
            if (is_array($el)) {
                $inner .= self::renderElement($el);
            }
        }

        return '<section style="' . $style . '">' . $inner . '</section>';
    }

    /* ------------------------------------------------------------------ */
    /*  Elementos                                                         */
    /* ------------------------------------------------------------------ */

    private static function renderElement(array $el): string
    {
        $type = $el['type'] ?? '';
        $node = '';

        switch ($type) {
            case 'heading':
                $node = self::renderHeading($el);
                break;
            case 'text':
                $node = self::renderText($el);
                break;
            case 'button':
                $node = self::renderButton($el);
                break;
            case 'image':
                $node = self::renderImage($el);
                break;
            case 'form':
                $node = self::renderForm($el);
                break;
        }

        if ($node === '') {
            return '';
        }

        // Cada elemento es un ítem flexible de la sección. Si está marcado como
        // "grow" (disposición en fila), ocupa el espacio disponible.
        $wrap = ! empty($el['grow']) ? 'flex:1;min-width:240px;' : '';

        return '<div style="' . $wrap . '">' . $node . '</div>';
    }

    private static function renderHeading(array $el): string
    {
        $tag    = in_array(($el['tag'] ?? 'h1'), ['h1', 'h2'], true) ? $el['tag'] : 'h1';
        $size   = self::num($el['size'] ?? 32, 8, 120);
        $weight = self::num($el['weight'] ?? 700, 100, 900);
        $color  = self::color($el['color'] ?? '#0F172A');
        $align  = self::align($el['align'] ?? 'center');

        $style = 'font-size:' . $size . 'px;font-weight:' . $weight . ';color:' . $color . ';'
            . 'text-align:' . $align . ';margin:0;line-height:1.35;';

        return '<' . $tag . ' style="' . $style . '">' . self::esc($el['text'] ?? '') . '</' . $tag . '>';
    }

    private static function renderText(array $el): string
    {
        $size  = self::num($el['size'] ?? 16, 8, 80);
        $color = self::color($el['color'] ?? '#475569');
        $align = self::align($el['align'] ?? 'center');

        $style = 'font-size:' . $size . 'px;color:' . $color . ';text-align:' . $align . ';'
            . 'margin:0;line-height:1.6;';

        return '<p style="' . $style . '">' . self::esc($el['text'] ?? '') . '</p>';
    }

    private static function renderButton(array $el): string
    {
        $label  = self::esc($el['label'] ?? 'Botón');
        $href   = self::url($el['href'] ?? '#');
        $bg     = self::color($el['bg'] ?? '#3B82F6');
        $color  = self::color($el['color'] ?? '#ffffff');
        $radius = self::num($el['radius'] ?? 10, 0, 60);
        $size   = self::num($el['size'] ?? 16, 8, 40);
        $align  = self::align($el['align'] ?? 'center');

        $btn = 'display:inline-block;background:' . $bg . ';color:' . $color . ';'
            . 'border-radius:' . $radius . 'px;padding:12px 22px;font-weight:700;'
            . 'font-size:' . $size . 'px;text-decoration:none;';

        return '<div style="text-align:' . $align . '">'
            . '<a href="' . $href . '" style="' . $btn . '">' . $label . '</a></div>';
    }

    private static function renderImage(array $el): string
    {
        $width = self::num($el['width'] ?? 60, 10, 100);
        $align = self::align($el['align'] ?? 'center');
        $src   = isset($el['src']) ? trim((string) $el['src']) : '';

        if ($src !== '') {
            $media = '<img src="' . self::url($src) . '" alt="" '
                . 'style="display:inline-block;width:' . $width . '%;height:auto;border-radius:12px">';
        } else {
            // Marcador de posición cuando aún no hay imagen.
            $media = '<div style="display:inline-block;width:' . $width . '%;aspect-ratio:16/9;'
                . 'border-radius:12px;background:linear-gradient(135deg,#93C5FD,#3B82F6)"></div>';
        }

        return '<div style="text-align:' . $align . '">' . $media . '</div>';
    }

    private static function renderForm(array $el): string
    {
        $align       = self::align($el['align'] ?? 'center');
        $submitLabel = self::esc($el['submitLabel'] ?? 'Enviar');
        $buttonBg    = self::color($el['buttonBg'] ?? '#3B82F6');
        $buttonColor = self::color($el['buttonColor'] ?? '#ffffff');
        $margin      = $align === 'center' ? '0 auto' : '0';

        // De momento el formulario se muestra pero no envía nada: la captura
        // real de leads se añade en el siguiente paso. onsubmit="return false"
        // evita que se recargue la página al pulsar el botón.
        $html = '<form onsubmit="return false" '
            . 'style="max-width:420px;margin:' . $margin . ';display:flex;flex-direction:column;gap:10px">';

        foreach (($el['fields'] ?? []) as $f) {
            if (is_array($f)) {
                $html .= self::renderField($f);
            }
        }

        $btn = 'background:' . $buttonBg . ';color:' . $buttonColor . ';border:0;border-radius:8px;'
            . 'padding:12px 18px;font-weight:700;font-size:15px;cursor:pointer;margin-top:4px;';
        $html .= '<button type="submit" style="' . $btn . '">' . $submitLabel . '</button>';

        return $html . '</form>';
    }

    private static function renderField(array $f): string
    {
        $label    = self::esc($f['label'] ?? 'Campo');
        $name     = self::esc($f['name'] ?? 'campo');
        $required = ! empty($f['required']);
        $type     = in_array(($f['type'] ?? 'text'), ['text', 'email', 'tel', 'checkbox'], true) ? $f['type'] : 'text';

        if ($type === 'checkbox') {
            return '<label style="display:flex;align-items:center;gap:8px;font-size:13px;color:#334155">'
                . '<input type="checkbox" name="' . $name . '"' . ($required ? ' required' : '') . '>'
                . $label . ($required ? ' *' : '') . '</label>';
        }

        $labelHtml = '<label style="display:block;font-size:12.5px;font-weight:600;margin-bottom:4px;color:#334155">'
            . $label . ($required ? ' *' : '') . '</label>';

        $input = '<input type="' . $type . '" name="' . $name . '" placeholder="' . $label . '"'
            . ($required ? ' required' : '')
            . ' style="width:100%;padding:9px 11px;border:1px solid #cbd5e1;border-radius:8px;font:inherit">';

        return '<div>' . $labelHtml . $input . '</div>';
    }

    /* ------------------------------------------------------------------ */
    /*  Saneado de valores (defensa básica de la salida)                  */
    /* ------------------------------------------------------------------ */

    /** Escapa texto para que no pueda romper el HTML. */
    private static function esc($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    /** Devuelve un color hexadecimal válido o un color por defecto. */
    private static function color($value): string
    {
        $value = is_string($value) ? trim($value) : '';

        return preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $value)
            ? $value
            : '#000000';
    }

    /** Convierte a entero y lo mantiene dentro de un rango. */
    private static function num($value, int $min, int $max): int
    {
        $n = (int) $value;

        return max($min, min($max, $n));
    }

    /** Normaliza la alineación a uno de los tres valores permitidos. */
    private static function align($value): string
    {
        return in_array($value, ['left', 'center', 'right'], true) ? $value : 'center';
    }

    /**
     * Sanea una URL para un href/src. Solo permite http(s), rutas relativas
     * y anclas; cualquier otra cosa (p. ej. javascript:) se neutraliza.
     */
    private static function url($value): string
    {
        $value = is_string($value) ? trim($value) : '';

        if ($value === '') {
            return '#';
        }

        if (preg_match('#^(https?://|/|\#)#i', $value)) {
            return self::esc($value);
        }

        // Si no empieza por un esquema reconocido, lo tratamos como dominio.
        if (preg_match('#^[a-z0-9.-]+\.[a-z]{2,}#i', $value)) {
            return self::esc('https://' . $value);
        }

        return '#';
    }
}
