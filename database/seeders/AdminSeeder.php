<?php

namespace Database\Seeders;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $rolAdministrador = Rol::firstWhere('nombre', 'Administrador');

        User::firstOrCreate(
            ['username' => 'admin'],
            [
                'rol_id' => $rolAdministrador->id,
                'ci' => '12345678',
                'name' => 'Administrador',
                'apellido' => 'Principal',
                'telefono' => '70000000',
                'email' => 'admin@cup.com',
                'password' => Hash::make('admin123'),
                'estado' => 'activo',
            ]
        );
    }
}