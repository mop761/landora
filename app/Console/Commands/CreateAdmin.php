<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * Crea (o convierte en administrador) un usuario pidiendo los datos por consola.
 * Pensado para producción: allí NO se debe ejecutar el seeder de pruebas, que crea
 * usuarios con contraseñas conocidas (admin@landora.test / password).
 *
 *   php artisan landora:admin
 */
class CreateAdmin extends Command
{
    protected $signature = 'landora:admin';

    protected $description = 'Crea o actualiza un usuario administrador pidiendo los datos por consola';

    public function handle(): int
    {
        $name = (string) $this->ask('Nombre');
        $email = (string) $this->ask('Email');
        // secret(): lo que se escribe no aparece en pantalla.
        $password = (string) $this->secret('Contraseña (mínimo 10 caracteres)');
        $confirm = (string) $this->secret('Repite la contraseña');

        $validator = Validator::make(compact('name', 'email', 'password'), [
            'name' => ['required', 'string', 'max:191'],
            'email' => ['required', 'email', 'max:191'],
            'password' => ['required', 'string', 'min:10'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        if ($password !== $confirm) {
            $this->error('Las contraseñas no coinciden.');

            return self::FAILURE;
        }

        // Si el email ya existe, se actualiza ese usuario; si no, se crea uno nuevo.
        $user = User::where('email', $email)->first() ?? new User(['email' => $email]);
        $nuevo = ! $user->exists;
        $user->name = $name;
        $user->password = Hash::make($password);   // nunca se guarda la contraseña en claro
        $user->role = 'admin';
        $user->save();

        $this->info(($nuevo ? 'Administrador creado: ' : 'Usuario actualizado como administrador: ') . $email);

        return self::SUCCESS;
    }
}
