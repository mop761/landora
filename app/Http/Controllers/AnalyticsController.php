<?php

namespace App\Http\Controllers;

use App\Models\Landing;
use App\Support\Analytics;
use Illuminate\Http\Request;

/**
 * Pantallas de analítica: el panel principal (todas las landings) y el detalle de cada landing.
 */
class AnalyticsController extends Controller
{
    /** Rangos de fechas que se pueden elegir (en días). */
    private const RANGOS = [7, 30, 90];

    /**
     * Panel principal: resumen de los últimos 30 días de todas las landings del usuario.
     */
    public function dashboard()
    {
        [$desde, $hasta] = $this->rango(30);

        $landings = Analytics::landingsDelUsuario(auth()->id(), $desde, $hasta);
        $serie = Analytics::porDiaUsuario(auth()->id(), $desde, $hasta);

        $unicos = array_sum(array_column($landings, 'unicos'));
        $leads = array_sum(array_column($landings, 'leads'));
        $totales = [
            'publicadas' => count(array_filter($landings, fn ($l) => $l['status'] === 'published')),
            'landings' => count($landings),
            'unicos' => $unicos,
            'leads' => $leads,
            'tasa' => Analytics::tasa($leads, $unicos),
        ];

        return view('dashboard', compact('landings', 'serie', 'totales'));
    }

    /**
     * Analítica de una landing: totales, gráfico diario, campañas y dispositivos.
     */
    public function landing(Request $request, Landing $landing)
    {
        abort_unless($landing->user_id === auth()->id(), 403);

        $dias = (int) $request->query('dias', 30);
        if (! in_array($dias, self::RANGOS, true)) {
            $dias = 30;
        }
        [$desde, $hasta] = $this->rango($dias);

        $resumen = Analytics::resumen($landing->id, $desde, $hasta);
        $serie = Analytics::porDia($landing->id, $desde, $hasta);
        $campanas = Analytics::porCampana($landing->id, $desde, $hasta);
        $dispositivos = Analytics::porDispositivo($landing->id, $desde, $hasta);
        $rangos = self::RANGOS;

        return view('landings.analytics', compact('landing', 'dias', 'rangos', 'resumen', 'serie', 'campanas', 'dispositivos'));
    }

    /**
     * Rango [desde, hasta): desde el inicio del día de hace ($dias - 1) días hasta el final de hoy.
     */
    private function rango(int $dias): array
    {
        return [
            now()->subDays($dias - 1)->startOfDay()->toDateTimeString(),
            now()->addDay()->startOfDay()->toDateTimeString(),
        ];
    }
}
