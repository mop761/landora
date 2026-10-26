<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Carga los datos iniciales de la aplicación.
     */
    public function run(): void
    {
        // Usuario de administración para poder entrar al panel.
        User::updateOrCreate(
            ['email' => 'admin@landora.test'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('password'),
            ]
        );
    }
}
