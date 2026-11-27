<?php

namespace Tests\Unit;

use App\Support\WebhookSender;
use PHPUnit\Framework\TestCase;

/**
 * Pruebas de la protección SSRF del webhook: no se puede enviar a la propia máquina
 * ni a redes privadas, y solo se admiten http y https.
 */
class WebhookSenderTest extends TestCase
{
    public function test_bloquea_localhost_y_redes_privadas(): void
    {
        foreach (['http://localhost/admin', 'http://127.0.0.1:8000', 'http://192.168.1.10/x', 'http://10.0.0.5', 'http://[::1]/'] as $url) {
            $this->assertNotNull(WebhookSender::urlBloqueada($url), "Debería bloquear $url");
        }
    }

    public function test_rechaza_esquemas_que_no_son_http(): void
    {
        $this->assertNotNull(WebhookSender::urlBloqueada('ftp://ejemplo.com'));
        $this->assertNotNull(WebhookSender::urlBloqueada('javascript:alert(1)'));
    }

    public function test_permite_una_ip_publica(): void
    {
        $this->assertNull(WebhookSender::urlBloqueada('https://8.8.8.8/hook'));
    }
}
