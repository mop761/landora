<?php

namespace App\Support;

/**
 * Lista de países con su prefijo telefónico. Datos propios del proyecto.
 * La usa el renderizador para montar el desplegable del campo "país".
 */
class Countries
{
    /** @var array<int, array{0:string,1:string}>  [nombre, prefijo] */
    private static array $list = [
        ['España', '+34'],
        ['Portugal', '+351'],
        ['Francia', '+33'],
        ['Italia', '+39'],
        ['Alemania', '+49'],
        ['Reino Unido', '+44'],
        ['Irlanda', '+353'],
        ['Países Bajos', '+31'],
        ['Bélgica', '+32'],
        ['Suiza', '+41'],
        ['Austria', '+43'],
        ['Andorra', '+376'],
        ['Luxemburgo', '+352'],
        ['Dinamarca', '+45'],
        ['Suecia', '+46'],
        ['Noruega', '+47'],
        ['Finlandia', '+358'],
        ['Polonia', '+48'],
        ['Grecia', '+30'],
        ['Rumanía', '+40'],
        ['Rusia', '+7'],
        ['Estados Unidos', '+1'],
        ['Canadá', '+1'],
        ['México', '+52'],
        ['Argentina', '+54'],
        ['Brasil', '+55'],
        ['Chile', '+56'],
        ['Colombia', '+57'],
        ['Perú', '+51'],
        ['Venezuela', '+58'],
        ['Ecuador', '+593'],
        ['Uruguay', '+598'],
        ['Paraguay', '+595'],
        ['Bolivia', '+591'],
        ['Guatemala', '+502'],
        ['Costa Rica', '+506'],
        ['Panamá', '+507'],
        ['Cuba', '+53'],
        ['República Dominicana', '+1'],
        ['Marruecos', '+212'],
        ['Argelia', '+213'],
        ['Túnez', '+216'],
        ['Egipto', '+20'],
        ['Sudáfrica', '+27'],
        ['Nigeria', '+234'],
        ['China', '+86'],
        ['Japón', '+81'],
        ['Corea del Sur', '+82'],
        ['India', '+91'],
        ['Australia', '+61'],
        ['Nueva Zelanda', '+64'],
        ['Turquía', '+90'],
        ['Israel', '+972'],
        ['Emiratos Árabes Unidos', '+971'],
        ['Arabia Saudí', '+966'],
        ['Catar', '+974'],
    ];

    /**
     * Devuelve las opciones ya formateadas como "País (+prefijo)",
     * que es lo que se mostrará y guardará en cada lead.
     */
    public static function options(): array
    {
        $out = [];
        foreach (self::$list as $c) {
            $out[] = $c[0] . ' (' . $c[1] . ')';
        }

        return $out;
    }
}
