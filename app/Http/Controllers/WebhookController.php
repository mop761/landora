<?php

namespace App\Http\Controllers;

use App\Models\Landing;
use App\Models\WebhookDelivery;
use App\Support\WebhookSender;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Configuración del webhook de cada landing y gestión de sus envíos.
 */
class WebhookController extends Controller
{
    /**
     * Página del webhook: URL, clave secreta, ejemplo del JSON y últimos envíos.
     */
    public function edit(Landing $landing)
    {
        $this->autorizar($landing);

        $entregas = $landing->webhookDeliveries()->latest()->limit(20)->get();
        $ejemplo = json_encode(WebhookSender::testPayload($landing), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return view('landings.webhook', compact('landing', 'entregas', 'ejemplo'));
    }

    /**
     * Guarda (o borra, si se deja vacía) la URL del webhook.
     */
    public function update(Request $request, Landing $landing)
    {
        $this->autorizar($landing);

        $data = $request->validate([
            'webhook_url' => ['nullable', 'url:http,https', 'max:500'],
        ]);
        $url = $data['webhook_url'] ?? null;

        if ($url !== null && ($motivo = WebhookSender::urlBloqueada($url)) !== null) {
            return back()->withInput()->withErrors(['webhook_url' => $motivo]);
        }

        $landing->webhook_url = $url;
        if ($url !== null && ! $landing->webhook_secret) {
            $landing->webhook_secret = Str::random(40);
        }
        $landing->save();

        return redirect()->route('landings.webhook', $landing)
            ->with('ok', $url !== null ? 'Webhook guardado.' : 'Webhook desactivado.');
    }

    /**
     * Genera una clave secreta nueva (la anterior deja de valer).
     */
    public function regenerateSecret(Landing $landing)
    {
        $this->autorizar($landing);

        $landing->webhook_secret = Str::random(40);
        $landing->save();

        return redirect()->route('landings.webhook', $landing)
            ->with('ok', 'Clave secreta regenerada. Actualízala también en el sistema que recibe el webhook.');
    }

    /**
     * Envía un JSON de prueba para comprobar que la URL funciona.
     */
    public function test(Landing $landing)
    {
        $this->autorizar($landing);
        abort_unless($landing->webhook_url, 422, 'Primero guarda una URL de webhook.');

        $envio = WebhookSender::send($landing, null, WebhookSender::testPayload($landing), 'webhook.test');

        return redirect()->route('landings.webhook', $landing)->with('ok', $envio->success
            ? 'Prueba enviada correctamente (respuesta ' . $envio->status_code . ').'
            : 'La prueba ha fallado: ' . $envio->error);
    }

    /**
     * Reintenta un envío: se vuelve a mandar y se registra como un intento nuevo.
     */
    public function retry(WebhookDelivery $delivery)
    {
        $landing = $delivery->landing;
        $this->autorizar($landing);

        if ($delivery->lead) {
            $envio = WebhookSender::send($landing, $delivery->lead, WebhookSender::leadPayload($delivery->lead), 'lead.created');
        } else {
            $envio = WebhookSender::send($landing, null, WebhookSender::testPayload($landing), 'webhook.test');
        }

        return back()->with('ok', $envio->success
            ? 'Reenviado correctamente (respuesta ' . $envio->status_code . ').'
            : 'El reintento ha fallado: ' . $envio->error);
    }

    /**
     * Solo el propietario de la landing puede tocar su webhook.
     */
    private function autorizar(Landing $landing): void
    {
        abort_unless($landing->user_id === auth()->id(), 403);
    }
}
