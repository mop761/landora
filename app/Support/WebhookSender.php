<?php

namespace App\Support;

use App\Models\Landing;
use App\Models\Lead;
use App\Models\WebhookDelivery;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Envía los leads de una landing a su webhook y deja registrado cada intento.
 *
 * Lógica propia del proyecto:
 *  - Monta el JSON que se envía (payload).
 *  - Lo firma con HMAC-SHA256 usando la clave secreta de la landing, para que quien
 *    lo recibe pueda comprobar que viene de Landora y que nadie lo ha modificado.
 *  - Bloquea URLs que apunten a la propia máquina o a redes privadas (protección SSRF).
 *  - Guarda el resultado (código, tiempo, error) en la tabla webhook_deliveries.
 * De Laravel solo se usa el cliente HTTP (Http::) para hacer la petición.
 */
class WebhookSender
{
    /** Segundos máximos para conectar y para recibir la respuesta. */
    private const CONNECT_TIMEOUT = 3;
    private const TIMEOUT = 5;

    /**
     * Envía $payload al webhook de la landing y devuelve el registro del envío.
     * Nunca lanza excepciones: cualquier fallo queda anotado en el registro.
     */
    public static function send(Landing $landing, ?Lead $lead, array $payload, string $event): WebhookDelivery
    {
        $url = (string) $landing->webhook_url;

        // Si la landing aún no tiene clave secreta, se genera ahora.
        if (! $landing->webhook_secret) {
            $landing->webhook_secret = Str::random(40);
            $landing->save();
        }

        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $firma = 'sha256=' . hash_hmac('sha256', $body, $landing->webhook_secret);

        $inicio = microtime(true);
        $status = null;
        $respuesta = null;
        $error = self::urlBloqueada($url);

        if ($error === null) {
            try {
                $res = Http::connectTimeout(self::CONNECT_TIMEOUT)
                    ->timeout(self::TIMEOUT)
                    ->withoutRedirecting()            // una redirección podría llevar a una red privada
                    ->withHeaders([
                        'User-Agent' => 'Landora-Webhook/1.0',
                        'X-Landora-Event' => $event,
                        'X-Landora-Signature' => $firma,
                    ])
                    ->withBody($body, 'application/json')
                    ->post($url);

                $status = $res->status();
                $respuesta = mb_substr($res->body(), 0, 1000);
                if (! $res->successful()) {
                    $error = 'El servidor respondió con el código ' . $status . '.';
                }
            } catch (\Throwable $e) {
                $error = 'No se pudo conectar: ' . mb_substr($e->getMessage(), 0, 300);
            }
        }

        return WebhookDelivery::create([
            'landing_id' => $landing->id,
            'lead_id' => $lead?->id,
            'event' => $event,
            'url' => mb_substr($url, 0, 500),
            'success' => $error === null,
            'status_code' => $status,
            'response_body' => $respuesta,
            'error' => $error !== null ? mb_substr($error, 0, 500) : null,
            'duration_ms' => (int) round((microtime(true) - $inicio) * 1000),
        ]);
    }

    /**
     * JSON que se envía cuando entra un lead real.
     */
    public static function leadPayload(Lead $lead): array
    {
        return [
            'event' => 'lead.created',
            'sent_at' => now()->toIso8601String(),
            'landing' => self::landingInfo($lead->landing),
            'lead' => [
                'id' => $lead->id,
                'created_at' => $lead->created_at->toIso8601String(),
                'data' => $lead->data,
                'utm' => [
                    'source' => $lead->utm_source,
                    'medium' => $lead->utm_medium,
                    'campaign' => $lead->utm_campaign,
                ],
            ],
        ];
    }

    /**
     * JSON de prueba (misma forma que uno real, con datos de ejemplo).
     */
    public static function testPayload(Landing $landing): array
    {
        return [
            'event' => 'webhook.test',
            'sent_at' => now()->toIso8601String(),
            'landing' => self::landingInfo($landing),
            'lead' => [
                'id' => null,
                'created_at' => now()->toIso8601String(),
                'data' => ['nombre' => 'Lead de prueba', 'email' => 'prueba@ejemplo.com'],
                'utm' => ['source' => 'landora', 'medium' => 'test', 'campaign' => null],
            ],
        ];
    }

    /**
     * Comprueba si una URL se puede usar como webhook. Devuelve el motivo si NO se puede,
     * o null si es válida. Evita que alguien use el webhook para atacar la red interna
     * del servidor (SSRF): no se permiten localhost ni rangos privados o reservados.
     */
    public static function urlBloqueada(?string $url): ?string
    {
        $partes = parse_url((string) $url);
        $esquema = strtolower($partes['scheme'] ?? '');

        if (! $partes || ! in_array($esquema, ['http', 'https'], true) || empty($partes['host'])) {
            return 'La URL del webhook no es válida (debe empezar por http:// o https://).';
        }

        $host = trim($partes['host'], '[]');
        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : (gethostbynamel($host) ?: []);

        if (! $ips) {
            return 'No se encuentra el servidor «' . $host . '».';
        }

        foreach ($ips as $ip) {
            if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return 'Por seguridad no se permiten direcciones locales o de red privada (' . $ip . ').';
            }
        }

        return null;
    }

    private static function landingInfo(Landing $landing): array
    {
        return [
            'id' => $landing->id,
            'name' => $landing->name,
            'slug' => $landing->slug,
            'url' => route('landings.show', $landing->slug),
        ];
    }
}
