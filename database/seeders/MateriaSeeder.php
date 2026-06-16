<?php

namespace Database\Seeders;

use App\Models\Materia;
use Illuminate\Database\Seeder;

class MateriaSeeder extends Seeder
{
    public function run(): void
    {
        $materias = [
            'Matemáticas',
            'Física',
            'Inglés',
            'Computación',
        ];

        foreach ($materias as $nombre) {
            Materia::updateOrCreate(
                ['nombre' => $nombre],
                [
                    'porcentaje_p1' => 30,
                    'porcentaje_p2' => 30,
                    'porcentaje_ef' => 40,
                    'nota_min_aprob' => 60,
                    'activo' => true,
                ]
            );
        }
    }
}