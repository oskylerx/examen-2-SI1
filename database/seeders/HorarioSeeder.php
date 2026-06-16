<?php

namespace Database\Seeders;

use App\Models\Horario;
use Illuminate\Database\Seeder;

class HorarioSeeder extends Seeder
{
    public function run(): void
    {
        $dias = [
            'lunes',
            'martes',
            'miercoles',
            'jueves',
            'viernes',
            'sabado',
        ];

        $bloques = [
            'mañana' => [
                ['08:00', '09:30'],
                ['10:00', '11:30'],
            ],
            'tarde' => [
                ['14:00', '15:30'],
                ['16:00', '17:30'],
            ],
            'noche' => [
                ['18:30', '20:00'],
                ['20:00', '21:30'],
            ],
        ];

        foreach ($dias as $dia) {
            foreach ($bloques as $turno => $horarios) {
                foreach ($horarios as [$horaInicio, $horaFinal]) {
                    Horario::updateOrCreate(
                        [
                            'dia' => $dia,
                            'turno' => $turno,
                            'hora_inicio' => $horaInicio,
                            'hora_final' => $horaFinal,
                        ],
                        [
                            'activo' => true,
                        ]
                    );
                }
            }
        }
    }
}