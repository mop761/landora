<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Consultas de analítica escritas en SQL propio (no con el ORM), con parámetros "?"
 * para evitar inyección SQL. De Laravel solo se usa DB::select() para ejecutarlas.
 *
 * Definiciones:
 *  - Visitas: cada carga de la landing publicada.
 *  - Visitantes únicos: huellas anónimas distintas (una por persona y día).
 *  - Leads: envíos del formulario.
 *  - Tasa de conversión: leads / visitantes únicos × 100.
 *
 * Las fechas llegan como texto 'Y-m-d H:i:s' y el rango es [desde, hasta).
 */
class Analytics
{
    /**
     * Totales de una landing en el rango: visitas, únicos, leads y tasa.
     */
    public static function resumen(int $landingId, string $desde, string $hasta): array
    {
        $v = DB::select(
            'SELECT COUNT(*) AS visitas, COUNT(DISTINCT visitor_hash) AS unicos
               FROM landing_visits
              WHERE landing_id = ? AND created_at >= ? AND created_at < ?',
            [$landingId, $desde, $hasta]
        )[0];

        $l = DB::select(
            'SELECT COUNT(*) AS leads
               FROM leads
              WHERE landing_id = ? AND created_at >= ? AND created_at < ?',
            [$landingId, $desde, $hasta]
        )[0];

        $unicos = (int) $v->unicos;
        $leads = (int) $l->leads;

        return [
            'visitas' => (int) $v->visitas,
            'unicos' => $unicos,
            'leads' => $leads,
            'tasa' => self::tasa($leads, $unicos),
        ];
    }

    /**
     * Serie diaria de una landing (para el gráfico): visitantes únicos y leads por día.
     */
    public static function porDia(int $landingId, string $desde, string $hasta): array
    {
        $visitas = DB::select(
            'SELECT DATE(created_at) AS dia, COUNT(DISTINCT visitor_hash) AS unicos
               FROM landing_visits
              WHERE landing_id = ? AND created_at >= ? AND created_at < ?
              GROUP BY DATE(created_at)',
            [$landingId, $desde, $hasta]
        );

        $leads = DB::select(
            'SELECT DATE(created_at) AS dia, COUNT(*) AS leads
               FROM leads
              WHERE landing_id = ? AND created_at >= ? AND created_at < ?
              GROUP BY DATE(created_at)',
            [$landingId, $desde, $hasta]
        );

        return self::rellenarDias($visitas, $leads, $desde, $hasta);
    }

    /**
     * Serie diaria de TODAS las landings de un usuario (gráfico del panel principal).
     * Se une con la tabla landings (JOIN) para quedarnos solo con las del usuario.
     */
    public static function porDiaUsuario(int $userId, string $desde, string $hasta): array
    {
        $visitas = DB::select(
            'SELECT DATE(v.created_at) AS dia, COUNT(DISTINCT v.visitor_hash) AS unicos
               FROM landing_visits v
               JOIN landings l ON l.id = v.landing_id
              WHERE l.user_id = ? AND v.created_at >= ? AND v.created_at < ?
              GROUP BY DATE(v.created_at)',
            [$userId, $desde, $hasta]
        );

        $leads = DB::select(
            'SELECT DATE(d.created_at) AS dia, COUNT(*) AS leads
               FROM leads d
               JOIN landings l ON l.id = d.landing_id
              WHERE l.user_id = ? AND d.created_at >= ? AND d.created_at < ?
              GROUP BY DATE(d.created_at)',
            [$userId, $desde, $hasta]
        );

        return self::rellenarDias($visitas, $leads, $desde, $hasta);
    }

    /**
     * Rendimiento por campaña (UTM): visitantes, leads y tasa de cada origen.
     * Las visitas y los leads están en tablas distintas, así que se agrupa cada una por su
     * lado y se juntan con UNION ALL; después se suman por campaña. Las visitas sin UTM
     * se muestran como "(directo)".
     */
    public static function porCampana(int $landingId, string $desde, string $hasta): array
    {
        $filas = DB::select(
            "SELECT fuente, medio, campana, SUM(unicos) AS unicos, SUM(leads) AS leads
               FROM (
                     SELECT COALESCE(utm_source, '(directo)') AS fuente,
                            COALESCE(utm_medium, '')          AS medio,
                            COALESCE(utm_campaign, '')        AS campana,
                            COUNT(DISTINCT visitor_hash)      AS unicos,
                            0                                 AS leads
                       FROM landing_visits
                      WHERE landing_id = ? AND created_at >= ? AND created_at < ?
                      GROUP BY fuente, medio, campana
                     UNION ALL
                     SELECT COALESCE(utm_source, '(directo)') AS fuente,
                            COALESCE(utm_medium, '')          AS medio,
                            COALESCE(utm_campaign, '')        AS campana,
                            0                                 AS unicos,
                            COUNT(*)                          AS leads
                       FROM leads
                      WHERE landing_id = ? AND created_at >= ? AND created_at < ?
                      GROUP BY fuente, medio, campana
                    ) t
              GROUP BY fuente, medio, campana
              ORDER BY leads DESC, unicos DESC",
            [$landingId, $desde, $hasta, $landingId, $desde, $hasta]
        );

        return array_map(fn ($f) => [
            'fuente' => $f->fuente,
            'medio' => $f->medio,
            'campana' => $f->campana,
            'unicos' => (int) $f->unicos,
            'leads' => (int) $f->leads,
            'tasa' => self::tasa((int) $f->leads, (int) $f->unicos),
        ], $filas);
    }

    /**
     * Visitantes únicos por tipo de dispositivo (móvil / escritorio).
     */
    public static function porDispositivo(int $landingId, string $desde, string $hasta): array
    {
        $filas = DB::select(
            'SELECT device, COUNT(DISTINCT visitor_hash) AS unicos
               FROM landing_visits
              WHERE landing_id = ? AND created_at >= ? AND created_at < ?
              GROUP BY device',
            [$landingId, $desde, $hasta]
        );

        $res = ['mobile' => 0, 'desktop' => 0];
        foreach ($filas as $f) {
            $res[$f->device] = (int) $f->unicos;
        }

        return $res;
    }

    /**
     * Resumen de cada landing de un usuario (tabla del panel principal).
     *
     * Ojo: si se unieran directamente landings + visitas + leads con dos LEFT JOIN, cada
     * visita se multiplicaría por cada lead y los recuentos saldrían inflados. Por eso se
     * agrupa primero cada tabla en una subconsulta (una fila por landing) y luego se une.
     * LEFT JOIN + COALESCE hace que salgan también las landings sin visitas ni leads (con 0).
     */
    public static function landingsDelUsuario(int $userId, string $desde, string $hasta): array
    {
        $filas = DB::select(
            'SELECT l.id, l.name, l.slug, l.status,
                    COALESCE(v.visitas, 0) AS visitas,
                    COALESCE(v.unicos, 0)  AS unicos,
                    COALESCE(d.leads, 0)   AS leads
               FROM landings l
               LEFT JOIN (SELECT landing_id, COUNT(*) AS visitas, COUNT(DISTINCT visitor_hash) AS unicos
                            FROM landing_visits
                           WHERE created_at >= ? AND created_at < ?
                           GROUP BY landing_id) v ON v.landing_id = l.id
               LEFT JOIN (SELECT landing_id, COUNT(*) AS leads
                            FROM leads
                           WHERE created_at >= ? AND created_at < ?
                           GROUP BY landing_id) d ON d.landing_id = l.id
              WHERE l.user_id = ?
              ORDER BY leads DESC, unicos DESC, l.name',
            [$desde, $hasta, $desde, $hasta, $userId]
        );

        return array_map(fn ($f) => [
            'id' => (int) $f->id,
            'name' => $f->name,
            'slug' => $f->slug,
            'status' => $f->status,
            'visitas' => (int) $f->visitas,
            'unicos' => (int) $f->unicos,
            'leads' => (int) $f->leads,
            'tasa' => self::tasa((int) $f->leads, (int) $f->unicos),
        ], $filas);
    }

    /**
     * Tasa de conversión en %, con un decimal. Si no hay visitantes, 0 (evita dividir entre 0).
     */
    public static function tasa(int $leads, int $unicos): float
    {
        return $unicos > 0 ? round($leads / $unicos * 100, 1) : 0.0;
    }

    /**
     * Las consultas solo devuelven los días que tienen datos; aquí se completa la serie
     * con todos los días del rango (los vacíos a 0) para que el gráfico sea continuo.
     */
    private static function rellenarDias(array $visitas, array $leads, string $desde, string $hasta): array
    {
        $u = [];
        foreach ($visitas as $f) {
            $u[(string) $f->dia] = (int) $f->unicos;
        }
        $l = [];
        foreach ($leads as $f) {
            $l[(string) $f->dia] = (int) $f->leads;
        }

        $serie = ['dias' => [], 'unicos' => [], 'leads' => []];
        $dia = new \DateTime(substr($desde, 0, 10));
        $fin = new \DateTime(substr($hasta, 0, 10));
        while ($dia < $fin) {
            $k = $dia->format('Y-m-d');
            $serie['dias'][] = $k;
            $serie['unicos'][] = $u[$k] ?? 0;
            $serie['leads'][] = $l[$k] ?? 0;
            $dia->modify('+1 day');
        }

        return $serie;
    }
}
