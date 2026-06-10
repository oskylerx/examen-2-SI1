<?php

namespace Database\Seeders;

use App\Models\Rol;
use Illuminate\Database\Seeder;

class RolSeeder extends Seeder
{
    public function run(): void
    {
        Rol::firstOrCreate(['nombre' => 'Administrador']);
        Rol::firstOrCreate(['nombre' => 'Docente']);
        Rol::firstOrCreate(['nombre' => 'Postulante']);
        Rol::firstOrCreate(['nombre' => 'Coordinador']);
    }
}