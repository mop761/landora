<?php

namespace App\Console\Commands;

use App\Models\Landing;
use Database\Seeders\DemoAnalyticsSeeder;
use Illuminate\Console\Command;

/**
 * Comando para generar (o borrar) datos de demostración de analítica en una landing concreta.
 *   php artisan landora:demo 5            -> genera 30 días de visitas y leads en la landing 5
 *   php artisan landora:demo mi-slug      -> también vale el slug
 *   php artisan landora:demo 5 --borrar   -> elimina solo los datos de demostración
 */
class DemoAnalytics extends Command
{
    protected $signature = 'landora:demo {landing : ID o slug de la landing} {--borrar : Elimina los datos de demostración}';

    protected $description = 'Genera (o borra) 30 días de visitas y leads de demostración para una landing';

    public function handle(): int
    {
        $valor = (string) $this->argument('landing');
        $landing = ctype_digit($valor) ? Landing::find((int) $valor) : Landing::where('slug', $valor)->first();

        if (! $landing) {
            $this->error('No existe ninguna landing con ID o slug «' . $valor . '».');

            return self::FAILURE;
        }

        $seeder = (new DemoAnalyticsSeeder)->setCommand($this);

        if ($this->option('borrar')) {
            [$v, $l] = $seeder->borrarPara($landing);
            $this->info('Borrados de «' . $landing->name . '»: ' . $v . ' visitas y ' . $l . ' leads de demostración.');

            return self::SUCCESS;
        }

        if ($landing->status !== 'published') {
            $this->warn('Aviso: la landing está en borrador. Los datos se crean igual, pero publícala para la demo.');
        }

        $seeder->generarPara($landing);

        return self::SUCCESS;
    }
}
