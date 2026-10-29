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
        // Usuario administrador.
        User::updateOrCreate(
            ['email' => 'admin@landora.test'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        // Usuario editor (para probar los permisos).
        User::updateOrCreate(
            ['email' => 'editor@landora.test'],
            [
                'name' => 'Editor',
                'password' => Hash::make('password'),
                'role' => 'editor',
            ]
        );
    }
}
