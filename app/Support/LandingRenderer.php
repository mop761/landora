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
 */
class LandingRenderer
{
    /** URL a la que se envía el formulario de la landing (captura de leads). */
    private static string $formAction = '';

    /** Token CSRF que acompaña al envío del formulario. */
    private static string $csrf = '';

    /** UTM de la visita (source, medium, campaign) que viajan ocultas en el formulario. */
    private static array $utm = [];

    /**
     * Devuelve el HTML de todas las secciones de la landing.
     *
     * $context puede traer 'action' (URL de envío del formulario) y 'csrf'
     * (token de seguridad). Si no vienen, el formulario se muestra pero no envía.
     */
    public static function render(?array $layout, array $context = []): string
    {
        self::$formAction = $context['action'] ?? '';
        self::$csrf = $context['csrf'] ?? '';
        self::$utm = is_array($context['utm'] ?? null) ? $context['utm'] : [];

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
        $elements = array_values(array_filter($s['elements'] ?? [], 'is_array'));

        // Compatibilidad con diseños antiguos (disposición "en fila" sin columnas).
        if (! isset($s['cols'])) {
            if (($s['dir'] ?? 'column') === 'row') {
                $s['cols'] = max(1, min(3, count($elements)));
                foreach ($elements as $i => $el) {
                    $elements[$i]['col'] = min($i, $s['cols'] - 1);
                }
            } else {
                $s['cols'] = 1;
            }
        }

        $cols   = self::num($s['cols'], 1, 3);
        $bg     = self::color($s['bg'] ?? '#ffffff');
        $padY   = self::num($s['padY'] ?? 44, 0, 300);
        $gap    = self::num($s['gap'] ?? 16, 0, 120);
        $colGap = self::num($s['colGap'] ?? 40, 0, 200);
        $valign = self::pick($s['valign'] ?? 'start', ['start', 'center', 'end'], 'start');

        // Ancho del contenido: completo, en px o en %.
        $mode = self::pick($s['widthMode'] ?? 'px', ['full', 'px', 'pct'], 'px');
        if ($mode === 'full') {
            $width = 'width:100%;';
        } elseif ($mode === 'pct') {
            $width = 'width:' . self::num($s['widthVal'] ?? 90, 10, 100) . '%;';
        } else {
            $width = 'width:100%;max-width:' . self::num($s['widthVal'] ?? ($s['max'] ?? 960), 240, 2400) . 'px;';
        }

        // Fondo a todo el ancho: color y, si hay, imagen. La "visibilidad" de la imagen
        // se consigue poniendo encima una capa del color de fondo semitransparente.
        $style = 'background-color:' . $bg . ';padding:' . $padY . 'px 24px;';
        $img = self::cssUrl($s['bgImg'] ?? '');
        if ($img !== '') {
            $visible = self::num($s['imgOp'] ?? 100, 0, 100);
            $capa = self::hexToRgba($bg, 1 - $visible / 100);
            $size = self::pick($s['bgSize'] ?? 'cover', ['cover', 'contain', 'auto'], 'cover');
            $pos  = self::pick($s['bgPos'] ?? 'center', ['center', 'top', 'bottom'], 'center');
            $style .= 'background-image:linear-gradient(' . $capa . ',' . $capa . "),url('" . $img . "');"
                . 'background-size:' . $size . ';background-position:' . $pos . ';background-repeat:no-repeat;';
        }

        $grid = 'display:grid;gap:' . $colGap . 'px;align-items:' . $valign . ';margin:0 auto;'
            . 'grid-template-columns:' . self::gridCols($cols, $s['ratio'] ?? '1-1') . ';' . $width;

        // Cada columna recibe, en orden, los elementos que tienen su número de columna.
        $html = '';
        for ($c = 0; $c < $cols; $c++) {
            $col = '';
            foreach ($elements as $el) {
                if (min(self::num($el['col'] ?? 0, 0, 2), $cols - 1) === $c) {
                    $col .= self::renderElement($el);
                }
            }
            $html .= '<div style="display:flex;flex-direction:column;gap:' . $gap . 'px;min-width:0">' . $col . '</div>';
        }

        return '<section style="' . $style . '"><div class="lnd-cols" style="' . $grid . '">' . $html . '</div></section>';
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
            case 'video':
                $node = self::renderVideo($el);
                break;
            case 'form':
                $node = self::renderForm($el);
                break;
        }

        if ($node === '') {
            return '';
        }

        // Estilo común del bloque (márgenes, relleno, fondo, opacidad).
        $wrap = self::blockStyle($el);

        // id y clase personalizados (solo caracteres seguros) para el CSS propio.
        $attrs = '';
        $id = self::cleanId($el['cssId'] ?? '');
        if ($id !== '') {
            $attrs .= ' id="' . $id . '"';
        }
        $class = self::cleanClass($el['cssClass'] ?? '');
        if ($class !== '') {
            $attrs .= ' class="' . $class . '"';
        }

        return '<div' . $attrs . ' style="' . $wrap . '">' . $node . '</div>';
    }

    private static function renderHeading(array $el): string
    {
        $tag    = self::pick($el['tag'] ?? 'h1', ['h1', 'h2'], 'h1');
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

    private static function renderVideo(array $el): string
    {
        $width = self::num($el['width'] ?? 100, 10, 100);
        $align = self::align($el['align'] ?? 'center');
        $src   = is_string($el['url'] ?? null) ? trim($el['url']) : '';
        $iframe = 'style="width:100%;height:100%;border:0" loading="lazy" allowfullscreen '
            . 'allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"';

        // Solo se usa el identificador del vídeo: nunca se pega la URL tal cual en el iframe.
        if (($id = self::youtubeId($src)) !== '') {
            $media = '<iframe src="https://www.youtube-nocookie.com/embed/' . $id . '" title="Vídeo" ' . $iframe . '></iframe>';
        } elseif (($id = self::vimeoId($src)) !== '') {
            $media = '<iframe src="https://player.vimeo.com/video/' . $id . '" title="Vídeo" ' . $iframe . '></iframe>';
        } elseif (preg_match('/\.(mp4|webm)(\?.*)?$/i', $src) && self::url($src) !== '#') {
            $media = '<video src="' . self::url($src) . '" controls preload="metadata" style="width:100%;height:100%;object-fit:cover"></video>';
        } else {
            return ''; // sin una URL de vídeo válida no se muestra nada
        }

        $box = 'display:inline-block;width:' . $width . '%;aspect-ratio:16/9;border-radius:12px;'
            . 'overflow:hidden;vertical-align:middle;background:#0f172a';

        return '<div style="text-align:' . $align . '"><div style="' . $box . '">' . $media . '</div></div>';
    }

    private static function renderForm(array $el): string
    {
        $align       = self::align($el['align'] ?? 'center');
        $submitLabel = self::esc($el['submitLabel'] ?? 'Enviar');
        $buttonBg    = self::color($el['buttonBg'] ?? '#3B82F6');
        $buttonColor = self::color($el['buttonColor'] ?? '#ffffff');
        $margin      = $align === 'center' ? '0 auto' : '0';
        $width       = self::num($el['width'] ?? 420, 200, 1200);
        $labelColor  = self::color($el['labelColor'] ?? '#334155');
        $formBg      = self::optColor($el['formBg'] ?? '');
        $borderW     = self::num($el['borderWidth'] ?? 0, 0, 10);
        $borderC     = self::color($el['borderColor'] ?? '#cbd5e1');
        $radius      = self::num($el['formRadius'] ?? 8, 0, 40);
        $pad         = self::num($el['formPad'] ?? 0, 0, 80);
        $styles      = 'width:100%;max-width:' . $width . 'px;margin:' . $margin . ';display:flex;flex-direction:column;gap:10px;'
            . 'border-radius:' . $radius . 'px;padding:' . $pad . 'px;'
            . ($formBg !== '' ? 'background:' . $formBg . ';' : '')
            . ($borderW > 0 ? 'border:' . $borderW . 'px solid ' . $borderC . ';' : '');

        // Si tenemos la URL de envío, el formulario es real (POST con token CSRF).
        // Si no (p. ej. una previsualización sin contexto), se muestra pero no envía.
        if (self::$formAction !== '') {
            $html = '<form method="POST" action="' . self::esc(self::$formAction) . '" style="' . $styles . '">'
                . '<input type="hidden" name="_token" value="' . self::esc(self::$csrf) . '">';
            foreach (['source', 'medium', 'campaign'] as $clave) {
                if (! empty(self::$utm[$clave]) && is_string(self::$utm[$clave])) {
                    $html .= '<input type="hidden" name="_utm_' . $clave . '" value="' . self::esc(self::$utm[$clave]) . '">';
                }
            }
        } else {
            $html = '<form onsubmit="return false" style="' . $styles . '">';
        }

        // Título y texto de introducción propios del formulario (opcionales).
        $titulo = is_string($el['title'] ?? null) ? trim($el['title']) : '';
        if ($titulo !== '') {
            $html .= '<h3 style="margin:0;font-size:22px;font-weight:700;color:'
                . self::color($el['titleColor'] ?? '#0F172A') . '">' . self::esc($titulo) . '</h3>';
        }
        $intro = is_string($el['intro'] ?? null) ? trim($el['intro']) : '';
        if ($intro !== '') {
            $html .= '<p style="margin:0 0 4px;font-size:14px;line-height:1.5;color:' . $labelColor . '">'
                . nl2br(self::esc($intro)) . '</p>';
        }

        foreach (($el['fields'] ?? []) as $f) {
            if (is_array($f)) {
                $html .= self::renderField($f, $labelColor);
            }
        }

        $btn = 'background:' . $buttonBg . ';color:' . $buttonColor . ';border:0;border-radius:8px;'
            . 'padding:12px 18px;font-weight:700;font-size:15px;cursor:pointer;margin-top:4px;';
        $html .= '<button type="submit" style="' . $btn . '">' . $submitLabel . '</button>';

        return $html . '</form>';
    }

    private static function renderField(array $f, string $labelColor = '#334155'): string
    {
        $label    = self::esc($f['label'] ?? 'Campo');
        $name     = self::esc($f['name'] ?? 'campo');
        $required = ! empty($f['required']);
        $type     = self::pick($f['type'] ?? 'text', ['text', 'email', 'tel', 'checkbox', 'select', 'pais'], 'text');

        if ($type === 'checkbox') {
            return '<label style="display:flex;align-items:center;gap:8px;font-size:13px;color:' . $labelColor . '">'
                . '<input type="checkbox" name="' . $name . '"' . ($required ? ' required' : '') . '>'
                . $label . ($required ? ' *' : '') . '</label>';
        }

        $labelHtml = '<label style="display:block;font-size:12.5px;font-weight:600;margin-bottom:4px;color:' . $labelColor . '">'
            . $label . ($required ? ' *' : '') . '</label>';

        // Desplegable: "pais" usa la lista de países del servidor; "select" usa las
        // opciones que el usuario escribió (una por línea).
        if ($type === 'select' || $type === 'pais') {
            $opciones = $type === 'pais' ? Countries::options() : self::optionList($f['options'] ?? '');
            $html = '<option value="">' . $label . '</option>';
            foreach ($opciones as $o) {
                $html .= '<option value="' . self::esc($o) . '">' . self::esc($o) . '</option>';
            }
            $select = '<select name="' . $name . '"' . ($required ? ' required' : '')
                . ' style="width:100%;padding:9px 11px;border:1px solid #cbd5e1;border-radius:8px;font:inherit;background:#fff">'
                . $html . '</select>';

            return '<div>' . $labelHtml . $select . '</div>';
        }

        $input = '<input type="' . $type . '" name="' . $name . '" placeholder="' . $label . '"'
            . ($required ? ' required' : '')
            . ' style="width:100%;padding:9px 11px;border:1px solid #cbd5e1;border-radius:8px;font:inherit">';

        return '<div>' . $labelHtml . $input . '</div>';
    }

    /** Convierte un texto (una opción por línea) en una lista de opciones. */
    private static function optionList($value): array
    {
        $value = is_string($value) ? $value : '';
        $out = [];
        foreach (preg_split('/\r\n|\r|\n/', $value) as $line) {
            $line = trim($line);
            if ($line !== '') {
                $out[] = $line;
            }
        }

        return $out;
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

    /** Color opcional: devuelve el hexadecimal si es válido, o '' si no hay color. */
    private static function optColor($value): string
    {
        $value = is_string($value) ? trim($value) : '';

        return preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $value) ? $value : '';
    }

    /** Estilo común de cualquier elemento: márgenes, relleno, fondo y opacidad. */
    private static function blockStyle(array $el): string
    {
        $css = '';
        $mt = self::num($el['mt'] ?? 0, 0, 200);
        $mb = self::num($el['mb'] ?? 0, 0, 200);
        if ($mt || $mb) {
            $css .= 'margin:' . $mt . 'px 0 ' . $mb . 'px;';
        }
        $pad = self::num($el['pad'] ?? 0, 0, 200);
        if ($pad) {
            $css .= 'padding:' . $pad . 'px;';
        }
        $bg = self::optColor($el['bgc'] ?? '');
        if ($bg !== '') {
            $css .= 'background:' . $bg . ';';
        }
        $op = self::num($el['op'] ?? 100, 0, 100);
        if ($op < 100) {
            $css .= 'opacity:' . ($op / 100) . ';';
        }

        return $css;
    }

    /** id válido: empieza por letra y solo letras, números, guion o guion bajo. */
    private static function cleanId($value): string
    {
        $value = is_string($value) ? trim($value) : '';

        return preg_match('/^[A-Za-z][A-Za-z0-9_-]{0,63}$/', $value) ? $value : '';
    }

    /** Clases válidas: se descartan las que tengan caracteres no permitidos. */
    private static function cleanClass($value): string
    {
        $value = is_string($value) ? $value : '';
        $ok = [];
        foreach (preg_split('/\s+/', trim($value)) as $c) {
            if (preg_match('/^[A-Za-z_-][A-Za-z0-9_-]*$/', $c)) {
                $ok[] = $c;
            }
        }

        return implode(' ', $ok);
    }

    /** Devuelve $value si está en la lista de valores permitidos; si no, el valor por defecto. */
    private static function pick($value, array $allowed, string $default): string
    {
        return in_array($value, $allowed, true) ? $value : $default;
    }

    /** Columnas del grid según el número de columnas y la proporción elegida. */
    private static function gridCols(int $cols, $ratio): string
    {
        if ($cols === 1) {
            return 'minmax(0,1fr)';
        }
        if ($cols === 3) {
            return 'repeat(3,minmax(0,1fr))';
        }
        $map = ['1-1' => [1, 1], '3-2' => [3, 2], '2-3' => [2, 3], '2-1' => [2, 1], '1-2' => [1, 2]];
        [$a, $b] = (is_string($ratio) && isset($map[$ratio])) ? $map[$ratio] : [1, 1];

        return 'minmax(0,' . $a . 'fr) minmax(0,' . $b . 'fr)';
    }

    /** Convierte un color hexadecimal (ya validado) en rgba() con la transparencia indicada. */
    private static function hexToRgba(string $hex, float $alpha): string
    {
        $h = ltrim($hex, '#');
        if (strlen($h) === 3) {
            $h = $h[0] . $h[0] . $h[1] . $h[1] . $h[2] . $h[2];
        }
        $h = substr($h, 0, 6);
        $alpha = round(max(0, min(1, $alpha)), 2);

        return 'rgba(' . hexdec(substr($h, 0, 2)) . ',' . hexdec(substr($h, 2, 2)) . ','
            . hexdec(substr($h, 4, 2)) . ',' . $alpha . ')';
    }

    /**
     * URL para usar dentro de url('...') en CSS: además del saneado normal,
     * se rechaza si trae comillas, paréntesis, barras invertidas o espacios.
     */
    private static function cssUrl($value): string
    {
        $url = self::url($value);
        if ($url === '#' || str_starts_with($url, '#')) {
            return '';
        }
        if (preg_match('/[\'"()\\\\\s]/', html_entity_decode($url, ENT_QUOTES))) {
            return '';
        }

        return $url;
    }

    /** Identificador de un vídeo de YouTube (11 caracteres) o '' si la URL no es de YouTube. */
    private static function youtubeId(string $url): string
    {
        return preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/))([A-Za-z0-9_-]{11})~', $url, $m)
            ? $m[1] : '';
    }

    /** Identificador numérico de un vídeo de Vimeo o '' si no lo es. */
    private static function vimeoId(string $url): string
    {
        return preg_match('~vimeo\.com/(?:video/)?(\d+)~', $url, $m) ? $m[1] : '';
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
