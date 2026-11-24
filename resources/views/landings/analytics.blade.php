@extends('layouts.panel')

@section('title', 'Analítica · ' . $landing->name . ' · Landora')

@section('content')
<h1>Analítica de «{{ $landing->name }}»</h1>

<div class="head-row">
    <div>
        <a class="btn-ghost" href="{{ route('landings.index') }}">&larr; Landings</a>
        <a class="btn-ghost" href="{{ route('landings.leads', $landing) }}">Ver leads</a>
    </div>
    <nav class="range" aria-label="Rango de fechas">
        @foreach ($rangos as $r)
            <a href="{{ route('landings.analytics', [$landing, 'dias' => $r]) }}" class="{{ $r === $dias ? 'on' : '' }}">{{ $r }} días</a>
        @endforeach
    </nav>
</div>

<div class="kpis">
    <div class="kpi">
        <div class="lbl">Visitas</div>
        <div class="num">{{ number_format($resumen['visitas'], 0, ',', '.') }}</div>
        <div class="sub">cargas de la página</div>
    </div>
    <div class="kpi">
        <div class="lbl">Visitantes únicos</div>
        <div class="num">{{ number_format($resumen['unicos'], 0, ',', '.') }}</div>
        <div class="sub">personas distintas por día</div>
    </div>
    <div class="kpi">
        <div class="lbl">Leads</div>
        <div class="num">{{ number_format($resumen['leads'], 0, ',', '.') }}</div>
        <div class="sub">formularios enviados</div>
    </div>
    <div class="kpi">
        <div class="lbl">Conversión</div>
        <div class="num">{{ number_format($resumen['tasa'], 1, ',', '.') }}%</div>
        <div class="sub">leads / visitantes únicos</div>
    </div>
</div>

<div class="grid2">
    <div class="card">
        <h2>Visitantes y leads por día</h2>
        <div class="chart-box"><canvas id="graficoDiario" aria-label="Gráfico de visitantes y leads por día" role="img"></canvas></div>
    </div>
    <div class="card">
        <h2>Dispositivos</h2>
        @if ($dispositivos['mobile'] + $dispositivos['desktop'] > 0)
            <div class="chart-box small"><canvas id="graficoDispositivos" aria-label="Gráfico de visitantes por dispositivo" role="img"></canvas></div>
        @else
            <p class="muted">Sin visitas en este periodo.</p>
        @endif
    </div>
</div>

<div class="card">
    <h2>Rendimiento por campaña</h2>
    @if (empty($campanas))
        <p class="muted">Sin datos en este periodo. Las campañas se detectan con los parámetros
            <code>utm_source</code>, <code>utm_medium</code> y <code>utm_campaign</code> de la URL.</p>
    @else
        <table class="tbl">
            <thead>
                <tr>
                    <th>Fuente</th>
                    <th>Medio</th>
                    <th>Campaña</th>
                    <th class="right">Visitantes</th>
                    <th class="right">Leads</th>
                    <th style="width:150px">Conversión</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($campanas as $c)
                    <tr>
                        <td><strong>{{ $c['fuente'] }}</strong></td>
                        <td>{{ $c['medio'] ?: '—' }}</td>
                        <td>{{ $c['campana'] ?: '—' }}</td>
                        <td class="right">{{ number_format($c['unicos'], 0, ',', '.') }}</td>
                        <td class="right">{{ number_format($c['leads'], 0, ',', '.') }}</td>
                        <td>
                            {{ number_format($c['tasa'], 1, ',', '.') }}%
                            <div class="bar"><span style="width:{{ min(100, $c['tasa'] * 5) }}%"></span></div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

<p class="muted" style="font-size:12px">
    Privacidad: no se guardan IPs de los visitantes. Cada visitante se identifica con una huella anónima
    (SHA-256) que cambia cada día. Tus propias visitas como propietario no se cuentan.
</p>

{{-- Datos para los gráficos (los dibuja public/js/analytics.js con Chart.js) --}}
<script>window.LANDORA_STATS = @json(['serie' => $serie, 'dispositivos' => $dispositivos]);</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script src="{{ asset('js/analytics.js') }}?v={{ filemtime(public_path('js/analytics.js')) }}"></script>
@endsection
