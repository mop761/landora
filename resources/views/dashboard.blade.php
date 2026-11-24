@extends('layouts.panel')

@section('title', 'Panel · Landora')

@section('content')
<h1>Panel</h1>
<p class="muted">
    Hola, <strong>{{ auth()->user()->name }}</strong>
    ({{ auth()->user()->isAdmin() ? 'Administrador' : 'Editor' }}). Este es el resumen de los últimos 30 días.
</p>

<div class="kpis">
    <div class="kpi">
        <div class="lbl">Landings publicadas</div>
        <div class="num">{{ $totales['publicadas'] }}</div>
        <div class="sub">de {{ $totales['landings'] }} en total</div>
    </div>
    <div class="kpi">
        <div class="lbl">Visitantes únicos</div>
        <div class="num">{{ number_format($totales['unicos'], 0, ',', '.') }}</div>
        <div class="sub">últimos 30 días</div>
    </div>
    <div class="kpi">
        <div class="lbl">Leads</div>
        <div class="num">{{ number_format($totales['leads'], 0, ',', '.') }}</div>
        <div class="sub">últimos 30 días</div>
    </div>
    <div class="kpi">
        <div class="lbl">Conversión</div>
        <div class="num">{{ number_format($totales['tasa'], 1, ',', '.') }}%</div>
        <div class="sub">leads / visitantes</div>
    </div>
</div>

<div class="card">
    <h2>Visitantes y leads por día</h2>
    <div class="chart-box"><canvas id="graficoDiario" aria-label="Gráfico de visitantes y leads por día" role="img"></canvas></div>
</div>

<div class="card">
    <h2>Tus landings (últimos 30 días)</h2>
    @if (empty($landings))
        <p class="muted">Aún no tienes landings. <a href="{{ route('landings.index') }}">Crea la primera</a>.</p>
    @else
        <table class="tbl">
            <thead>
                <tr>
                    <th>Landing</th>
                    <th>Estado</th>
                    <th class="right">Visitantes</th>
                    <th class="right">Leads</th>
                    <th style="width:160px">Conversión</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($landings as $l)
                    <tr>
                        <td>{{ $l['name'] }}<br><code>/{{ $l['slug'] }}</code></td>
                        <td>
                            @if ($l['status'] === 'published')
                                <span class="badge" style="background:#dcfce7;color:#166534">Publicada</span>
                            @else
                                <span class="badge" style="background:#f1f5f9;color:#64748b">Borrador</span>
                            @endif
                        </td>
                        <td class="right">{{ number_format($l['unicos'], 0, ',', '.') }}</td>
                        <td class="right">{{ number_format($l['leads'], 0, ',', '.') }}</td>
                        <td>
                            {{ number_format($l['tasa'], 1, ',', '.') }}%
                            <div class="bar"><span style="width:{{ min(100, $l['tasa'] * 5) }}%"></span></div>
                        </td>
                        <td class="right"><a class="btn-ghost" href="{{ route('landings.analytics', $l['id']) }}">Ver analítica</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p class="muted" style="font-size:12px;margin:10px 0 0">La barra de conversión llega al máximo con un 20%.</p>
    @endif
</div>

{{-- Datos para los gráficos (los dibuja public/js/analytics.js con Chart.js) --}}
<script>window.LANDORA_STATS = @json(['serie' => $serie]);</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script src="{{ asset('js/analytics.js') }}?v={{ filemtime(public_path('js/analytics.js')) }}"></script>
@endsection
