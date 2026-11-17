@extends('layouts.panel')

@section('title', 'Leads · ' . $landing->name . ' · Landora')

@section('content')
<h1>Leads de «{{ $landing->name }}»</h1>

<p><a class="btn-ghost" href="{{ route('landings.index') }}">&larr; Volver a landings</a></p>

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
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
