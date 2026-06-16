<?php

namespace Database\Seeders;

use App\Models\Aula;
use Illuminate\Database\Seeder;

class AulaSeeder extends Seeder
{
    public function run(): void
    {
        $pisos = [
            1 => range(11, 16),
            2 => range(21, 26),
            3 => range(31, 36),
            4 => range(41, 46),
        ];

        foreach ($pisos as $piso => $aulas) {
            foreach ($aulas as $numero) {
                Aula::updateOrCreate(
                    ['nombre' => 'Aula ' . $numero],
                    [
                        'capacidad' => 70,
                        'ubicacion' => 'Piso ' . $piso,
                        'activo' => true,
                    ]
                );
            }
        }
    }
}