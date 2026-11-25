<?php

namespace App\Support;

use App\Models\Landing;
use Illuminate\Support\Str;

/**
 * Reglas de los slugs (la parte de la URL pública de cada landing: midominio.com/<slug>).
 *
 * La ruta pública /{slug} está declarada al final de routes/web.php, así que las rutas del
 * sistema (/login, /dashboard...) tienen prioridad. Si una landing tuviera el slug "login",
 * nunca se podría ver. Por eso esas palabras están reservadas y no se pueden usar como slug.
 */
class Slugs
{
    /** Palabras que ya usa la aplicación (rutas y carpetas públicas) y no pueden ser slug. */
    public const RESERVADOS = [
        'login', 'logout', 'register', 'password', 'dashboard', 'admin', 'landings',
        'webhook-deliveries', 'api', 'up', 'storage', 'css', 'js', 'build', 'img', 'images',
    ];

    /** Formato válido: minúsculas, números y guiones simples entre palabras. */
    public const FORMATO = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    public static function esReservado(string $slug): bool
    {
        return in_array($slug, self::RESERVADOS, true);
    }

    /**
     * Genera un slug libre a partir de un nombre: prueba "nombre", "nombre-2", "nombre-3"...
     * hasta encontrar uno que no use otra landing. Si el nombre da una palabra reservada,
     * se le añade "-landing" (p. ej. "login" -> "login-landing").
     */
    public static function unico(string $nombre): string
    {
        $base = Str::slug($nombre) ?: 'landing';
        if (self::esReservado($base)) {
            $base .= '-landing';
        }

        $slug = $base;
        $i = 1;
        while (Landing::where('slug', $slug)->exists()) {
            $slug = $base . '-' . (++$i);
        }

        return $slug;
    }

    /**
     * ¿La excepción de la base de datos es por un valor duplicado en una columna UNIQUE?
     * (Código 1062 de MySQL: "Duplicate entry".)
     */
    public static function esDuplicado(\Illuminate\Database\QueryException $e): bool
    {
        return (int) ($e->errorInfo[1] ?? 0) === 1062;
    }
}
