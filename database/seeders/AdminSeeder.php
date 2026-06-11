<?php

namespace Database\Seeders;

use App\Models\Rol;
use App\Models\User;
use App\Models\Administrador;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Asegurar que el rol Administrador exista
        $rolAdministrador = Rol::firstOrCreate(['nombre' => 'Administrador']);

        // Crear o actualizar el usuario Administrador
        $adminUser = User::updateOrCreate(
            ['username' => 'admin'],
            [
                'rol_id' => $rolAdministrador->id,
                'ci' => '12345678',
                'name' => 'Administrador',
                'apellido' => 'Principal',
                'telefono' => '70000000',
                'email' => 'admin@cup.com',
                'password' => Hash::make('1234'), // Contraseña hasheada
                'estado' => 'activo',
            ]
        );

        // Crear o actualizar el registro correspondiente en la tabla administrador
        Administrador::updateOrCreate(
            ['user_id' => $adminUser->id],
            []
        );
    }
}
