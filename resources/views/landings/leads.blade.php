@extends('layouts.panel')

@section('title', 'Leads · ' . $landing->name . ' · Landora')

@section('content')
<h1>Leads de «{{ $landing->name }}»</h1>

<p>
    <a class="btn-ghost" href="{{ route('landings.index') }}">&larr; Volver a landings</a>
    <a class="btn-ghost" href="{{ route('landings.webhook', $landing) }}">Configurar webhook</a>
</p>

@if (session('ok'))
    <div class="flash">{{ session('ok') }}</div>
@endif

<div class="card">
    @if ($leads->isEmpty())
        <p class="muted">Esta landing todavía no ha recibido ningún lead.</p>
    @else
        <p class="muted">{{ $leads->count() }} {{ $leads->count() === 1 ? 'lead recibido' : 'leads recibidos' }}.</p>
        <table class="tbl">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Datos del contacto</th>
                    <th>Origen</th>
                    <th>Webhook</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($leads as $lead)
                    <tr>
                        <td>{{ $lead->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            @foreach ($lead->data as $campo => $valor)
                                <strong>{{ $campo }}:</strong>
                                {{ is_array($valor) ? implode(', ', $valor) : $valor }}<br>
                            @endforeach
                        </td>
                        <td style="font-size:12.5px">
                            @if ($lead->utm_source)
                                {{ $lead->utm_source }}@if ($lead->utm_medium) / {{ $lead->utm_medium }}@endif
                                @if ($lead->utm_campaign)<br><span class="muted">{{ $lead->utm_campaign }}</span>@endif
                            @else
                                <span class="muted">Directo</span>
                            @endif
                        </td>
                        <td>
                            @php $envio = $lead->latestDelivery; @endphp
                            @if (! $envio)
                                <span class="muted">—</span>
                            @elseif ($envio->success)
                                <span class="badge" style="background:#dcfce7;color:#166534">Enviado</span>
                            @else
                                <span class="badge" style="background:#fee2e2;color:#991b1b" title="{{ $envio->error }}">Error</span>
                                <form method="POST" action="{{ route('webhook.retry', $envio) }}" style="display:inline">
                                    @csrf
                                    <button type="submit" class="btn-sm">Reintentar</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
