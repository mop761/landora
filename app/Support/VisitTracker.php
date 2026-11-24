<?php

namespace App\Support;

use App\Models\Landing;
use App\Models\LandingVisit;
use Illuminate\Http\Request;

/**
 * Registra las visitas a las landings publicadas (lógica propia del proyecto).
 *
 * Privacidad: no se guarda la IP. Se calcula una huella anónima con SHA-256 a partir de
 * IP + navegador + fecha del día + la clave de la aplicación. Sirve para contar visitantes
 * únicos de cada día, pero no permite saber quién es ni seguirle de un día a otro.
 */
class VisitTracker
{
    /** Navegadores que en realidad son robots o herramientas: no cuentan como visitas. */
    private const BOTS = '/bot|crawl|spider|slurp|preview|facebookexternalhit|curl|wget|python|headless/i';

    public static function record(Request $request, Landing $landing): void
    {
        $ua = (string) $request->userAgent();
        if ($ua === '' || preg_match(self::BOTS, $ua)) {
            return;
        }

        LandingVisit::create([
            'landing_id' => $landing->id,
            'visitor_hash' => self::visitorHash((string) $request->ip(), $ua),
            'utm_source' => self::texto($request->query('utm_source')),
            'utm_medium' => self::texto($request->query('utm_medium')),
            'utm_campaign' => self::texto($request->query('utm_campaign')),
            'referrer' => self::dominio($request->headers->get('referer'), $request->getHost()),
            'device' => preg_match('/Mobi|Android|iPhone|iPad/i', $ua) ? 'mobile' : 'desktop',
        ]);
    }

    /** Huella anónima del visitante para un día concreto (por defecto, hoy). */
    public static function visitorHash(string $ip, string $ua, ?string $dia = null): string
    {
        return hash('sha256', $ip . '|' . $ua . '|' . ($dia ?? now()->toDateString()) . '|' . config('app.key'));
    }

    /** Texto corto y limpio (máx. 100 caracteres) o null. */
    private static function texto($valor): ?string
    {
        return (is_string($valor) && trim($valor) !== '') ? mb_substr(trim($valor), 0, 100) : null;
    }

    /** Dominio de procedencia (p. ej. "www.google.com"); null si viene de la propia web o no hay. */
    private static function dominio(?string $url, string $propio): ?string
    {
        $host = $url ? parse_url($url, PHP_URL_HOST) : null;
        if (! is_string($host) || strcasecmp($host, $propio) === 0) {
            return null;
        }

        return mb_substr(strtolower($host), 0, 100);
    }
}
