<?php

namespace Database\Seeders;

use App\Models\Landing;
use App\Support\Countries;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Datos de DEMOSTRACIÓN para la analítica: 30 días de visitas y leads con varias campañas.
 * Sirve para enseñar los gráficos en la presentación sin esperar a tener tráfico real.
 *
 * Uso recomendado (eligiendo la landing por su ID o su slug):
 *     php artisan landora:demo 5
 *     php artisan landora:demo 5 --borrar      (elimina los datos de demostración)
 * También se puede lanzar como seeder (usa la landing publicada más reciente):
 *     php artisan db:seed --class=DemoAnalyticsSeeder
 *
 * Los datos de demostración se reconocen porque las visitas tienen la huella "demo…" y los
 * leads el navegador "Landora-Demo"; así se pueden borrar sin tocar los datos reales.
 */
class DemoAnalyticsSeeder extends Seeder
{
    /** [fuente, medio, campaña, dominio de origen, peso en %, conversión en %] */
    private const FUENTES = [
        [null, null, null, null, 30, 4.0],
        ['google', 'cpc', 'black-friday', 'www.google.com', 32, 6.5],
        ['facebook', 'paid', 'remarketing', 'l.facebook.com', 20, 3.0],
        ['instagram', 'social', 'stories', 'l.instagram.com', 12, 2.0],
        ['newsletter', 'email', 'noviembre', null, 6, 9.0],
    ];

    private const NOMBRES = ['Lucía', 'Hugo', 'Martina', 'Pablo', 'Sofía', 'Daniel', 'Valeria', 'Álvaro', 'Carmen',
        'Javier', 'Elena', 'Marcos', 'Irene', 'Sergio', 'Paula', 'Adrián', 'Noelia', 'Rubén', 'Claudia', 'Óscar'];
    private const APELLIDOS = ['García', 'Martínez', 'López', 'Sánchez', 'Pérez', 'Gómez', 'Fernández', 'Ruiz',
        'Díaz', 'Moreno', 'Navarro', 'Romero', 'Torres', 'Castro', 'Ortega', 'Delgado'];
    private const DOMINIOS = ['gmail.com', 'hotmail.com', 'outlook.es', 'yahoo.es', 'icloud.com'];

    /** Como seeder: usa la landing publicada más reciente. */
    public function run(): void
    {
        $landing = Landing::where('status', 'published')->latest()->first() ?? Landing::latest()->first();
        if (! $landing) {
            $this->command->warn('No hay ninguna landing. Crea una primero.');

            return;
        }
        $this->generarPara($landing);
    }

    /** Borra los datos de demostración de una landing (nunca los reales). Devuelve [visitas, leads]. */
    public function borrarPara(Landing $landing): array
    {
        $v = DB::table('landing_visits')->where('landing_id', $landing->id)->where('visitor_hash', 'like', 'demo%')->delete();
        $l = DB::table('leads')->where('landing_id', $landing->id)->where('user_agent', 'Landora-Demo')->delete();

        return [$v, $l];
    }

    /** Genera 30 días de visitas y leads de demostración para la landing indicada. */
    public function generarPara(Landing $landing): void
    {
        $this->borrarPara($landing);   // si ya había datos de demostración, se sustituyen
        $campos = $this->camposDelFormulario($landing);

        $visitas = [];
        $leads = [];

        for ($d = 29; $d >= 0; $d--) {
            $dia = now()->subDays($d)->startOfDay();
            // Tráfico con algo de tendencia creciente y variación diaria.
            $n = random_int(30, 60) + (int) ((29 - $d) * 1.2);

            for ($i = 0; $i < $n; $i++) {
                [$fuente, $medio, $campana, $origen, , $conversion] = $this->fuenteAleatoria();
                $momento = $dia->copy()->addMinutes(random_int(8 * 60, 23 * 60));
                // ~15 % de las visitas son de alguien que ya había entrado ese día (misma huella).
                $persona = random_int(1, 100) <= 15 ? random_int(0, max(0, $i - 1)) : $i;

                $visitas[] = [
                    'landing_id' => $landing->id,
                    'visitor_hash' => 'demo' . substr(hash('sha256', $landing->id . '-' . $d . '-' . $persona), 0, 60),
                    'utm_source' => $fuente,
                    'utm_medium' => $medio,
                    'utm_campaign' => $campana,
                    'referrer' => $origen,
                    'device' => random_int(1, 100) <= 64 ? 'mobile' : 'desktop',
                    'created_at' => $momento,
                ];

                if (random_int(1, 1000) <= $conversion * 10) {
                    $enviado = $momento->copy()->addMinutes(random_int(1, 6));
                    $leads[] = [
                        'landing_id' => $landing->id,
                        'data' => json_encode($this->datosLead($campos), JSON_UNESCAPED_UNICODE),
                        'utm_source' => $fuente,
                        'utm_medium' => $medio,
                        'utm_campaign' => $campana,
                        'ip' => null,
                        'user_agent' => 'Landora-Demo',
                        'created_at' => $enviado,
                        'updated_at' => $enviado,
                    ];
                }
            }
        }

        // Inserción por bloques para no lanzar una sola consulta enorme.
        foreach (array_chunk($visitas, 500) as $bloque) {
            DB::table('landing_visits')->insert($bloque);
        }
        foreach (array_chunk($leads, 500) as $bloque) {
            DB::table('leads')->insert($bloque);
        }

        $this->command?->info('Datos de demostración creados para «' . $landing->name . '» (ID ' . $landing->id . '): '
            . count($visitas) . ' visitas y ' . count($leads) . ' leads.');
    }

    /**
     * Campos del primer formulario del diseño de la landing, para que los leads de prueba
     * tengan los mismos campos que el formulario real. Si no hay formulario, unos por defecto.
     */
    private function camposDelFormulario(Landing $landing): array
    {
        foreach (($landing->layout['sections'] ?? []) as $s) {
            foreach (($s['elements'] ?? []) as $el) {
                if (($el['type'] ?? '') === 'form' && ! empty($el['fields']) && is_array($el['fields'])) {
                    return $el['fields'];
                }
            }
        }

        return [
            ['name' => 'nombre', 'type' => 'text'],
            ['name' => 'apellidos', 'type' => 'text'],
            ['name' => 'email', 'type' => 'email'],
            ['name' => 'telefono', 'type' => 'tel'],
        ];
    }

    /** Inventa los datos de un lead, con un valor coherente para cada tipo de campo. */
    private function datosLead(array $campos): array
    {
        $nombre = self::NOMBRES[array_rand(self::NOMBRES)];
        $apellido = self::APELLIDOS[array_rand(self::APELLIDOS)] . ' ' . self::APELLIDOS[array_rand(self::APELLIDOS)];
        $datos = [];

        foreach ($campos as $f) {
            $clave = (string) ($f['name'] ?? 'campo');
            $tipo = (string) ($f['type'] ?? 'text');

            $datos[$clave] = match (true) {
                $tipo === 'email' => strtolower($this->sinTildes($nombre . '.' . strtok($apellido, ' ')))
                    . random_int(1, 99) . '@' . self::DOMINIOS[array_rand(self::DOMINIOS)],
                $tipo === 'tel' => '6' . random_int(10, 99) . ' ' . random_int(100, 999) . ' ' . random_int(100, 999),
                $tipo === 'pais' => random_int(1, 100) <= 80 ? 'España (+34)' : $this->aleatorio(Countries::options()),
                $tipo === 'select' => $this->aleatorio($this->opciones($f['options'] ?? '')) ?? 'Opción 1',
                $tipo === 'checkbox' => 'on',
                str_contains($clave, 'apell') => $apellido,
                str_contains($clave, 'nom') => $nombre,
                default => 'Respuesta de prueba',
            };
        }

        return $datos;
    }

    private function opciones($texto): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $texto))));
    }

    private function aleatorio(array $lista): ?string
    {
        return $lista ? $lista[array_rand($lista)] : null;
    }

    /** Elige una fuente de tráfico según su peso. */
    private function fuenteAleatoria(): array
    {
        $tirada = random_int(1, 100);
        $acumulado = 0;
        foreach (self::FUENTES as $f) {
            $acumulado += $f[4];
            if ($tirada <= $acumulado) {
                return $f;
            }
        }

        return self::FUENTES[0];
    }

    private function sinTildes(string $texto): string
    {
        return strtr($texto, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'Á' => 'A', 'É' => 'E',
            'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'ñ' => 'n', 'Ñ' => 'N']);
    }
}
