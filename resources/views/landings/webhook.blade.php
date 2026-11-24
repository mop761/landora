@extends('layouts.panel')

@section('title', 'Webhook · ' . $landing->name . ' · Landora')

@section('content')
<h1>Webhook de «{{ $landing->name }}»</h1>

<p><a class="btn-ghost" href="{{ route('landings.index') }}">&larr; Volver a landings</a></p>

@if (session('ok'))
    <div class="flash">{{ session('ok') }}</div>
@endif

<div class="card">
    <h2>Dirección del webhook</h2>
    <p class="muted">Cada vez que alguien envíe el formulario de esta landing, Landora mandará los datos del lead
        en formato JSON (petición POST) a esta dirección. Déjala vacía para desactivarlo.</p>
    <form method="POST" action="{{ route('landings.webhook.update', $landing) }}" class="inline-form">
        @csrf
        @method('PUT')
        <input type="url" name="webhook_url" placeholder="https://..." style="flex:1;min-width:280px"
               value="{{ old('webhook_url', $landing->webhook_url) }}">
        <button type="submit" class="btn-sm">Guardar</button>
    </form>
    @error('webhook_url')<p class="err">{{ $message }}</p>@enderror

    @if ($landing->webhook_url)
        <form method="POST" action="{{ route('landings.webhook.test', $landing) }}" style="margin-top:12px">
            @csrf
            <button type="submit" class="btn-ghost">Enviar una prueba</button>
        </form>
    @endif
</div>

@if ($landing->webhook_secret)
<div class="card">
    <h2>Clave secreta (firma de los envíos)</h2>
    <p class="muted">Cada envío lleva la cabecera <code>X-Landora-Signature: sha256=…</code>, que es el
        HMAC-SHA256 del cuerpo JSON calculado con esta clave. Quien recibe el webhook puede recalcularlo
        para comprobar que los datos vienen de Landora y no han sido modificados.</p>
    <p><code style="word-break:break-all">{{ $landing->webhook_secret }}</code></p>
    <form method="POST" action="{{ route('landings.webhook.secret', $landing) }}"
          onsubmit="return confirm('La clave actual dejará de valer. ¿Continuar?')">
        @csrf
        <button type="submit" class="btn-ghost">Regenerar clave</button>
    </form>
</div>
@endif

<div class="card">
    <h2>Últimos envíos</h2>
    @if ($entregas->isEmpty())
        <p class="muted">Todavía no se ha enviado nada a este webhook.</p>
    @else
        <table class="tbl">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Evento</th>
                    <th>Resultado</th>
                    <th>Tiempo</th>
                    <th>Detalle</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($entregas as $e)
                    <tr>
                        <td>{{ $e->created_at->format('d/m/Y H:i:s') }}</td>
                        <td><code>{{ $e->event }}</code></td>
                        <td>
                            @if ($e->success)
                                <span class="badge" style="background:#dcfce7;color:#166534">OK {{ $e->status_code }}</span>
                            @else
                                <span class="badge" style="background:#fee2e2;color:#991b1b">Error {{ $e->status_code }}</span>
                            @endif
                        </td>
                        <td>{{ $e->duration_ms }} ms</td>
                        <td style="max-width:320px;font-size:12px;word-break:break-word">
                            {{ $e->error ?? \Illuminate\Support\Str::limit((string) $e->response_body, 120) }}
                        </td>
                        <td class="right">
                            @unless ($e->success)
                                <form method="POST" action="{{ route('webhook.retry', $e) }}">
                                    @csrf
                                    <button type="submit" class="btn-sm">Reintentar</button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

<div class="card">
    <h2>Ejemplo del JSON que se envía</h2>
    <pre style="background:#0f172a;color:#e2e8f0;padding:14px;border-radius:8px;overflow:auto;font-size:12px">{{ $ejemplo }}</pre>
</div>
@endsection
