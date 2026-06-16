<?php

namespace Database\Seeders;

use App\Models\AsignacionAcademica;
use App\Models\Calificacion;
use App\Models\Examen;
use App\Models\Materia;
use App\Models\Postulante;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CalificacionMasivaSeeder extends Seeder
{
    public function run(): void
    {
        $gestion = '2026-1';

        $postulantes = Postulante::with('grupo')
            ->where('estado_inscripcion', 'aceptado')
            ->whereNotNull('grupo_id')
            ->orderBy('id')
            ->get();

        if ($postulantes->isEmpty()) {
            throw new \Exception('No existen postulantes aceptados con grupo asignado.');
        }

        $materiasActivas = Materia::where('activo', true)
            ->orderBy('id')
            ->get();

        if ($materiasActivas->isEmpty()) {
            throw new \Exception('No existen materias activas registradas.');
        }

        $examenesCreados = 0;
        $calificacionesCreadas = 0;

        DB::transaction(function () use (
            $postulantes,
            $materiasActivas,
            $gestion,
            &$examenesCreados,
            &$calificacionesCreadas
        ) {
            foreach ($postulantes as $index => $postulante) {
                $materiasDelGrupo = $this->obtenerMateriasDelGrupo(
                    $postulante->grupo_id,
                    $gestion,
                    $materiasActivas
                );

                foreach ($materiasDelGrupo as $materia) {
                    [$p1, $p2, $ef] = $this->generarNotas($index);

                    $promedio = $this->calcularPromedio($p1, $p2, $ef, $materia);

                    $estado = $promedio >= ($materia->nota_min_aprob ?? 60)
                        ? 'aprobado'
                        : 'reprobado';

                    $examen = Examen::updateOrCreate(
                        [
                            'materia_id' => $materia->id,
                            'postulante_id' => $postulante->id,
                        ],
                        [
                            'fecha_registro' => now()->format('Y-m-d'),
                            'promedio_final' => $promedio,
                            'estado' => $estado,
                        ]
                    );

                    $this->guardarCalificacion($examen->id, 'p1', $p1);
                    $this->guardarCalificacion($examen->id, 'p2', $p2);
                    $this->guardarCalificacion($examen->id, 'ef', $ef);

                    $examenesCreados++;
                    $calificacionesCreadas += 3;
                }
            }
        });

        $this->command->info("Seeder finalizado.");
        $this->command->info("Exámenes procesados: {$examenesCreados}");
        $this->command->info("Calificaciones procesadas: {$calificacionesCreadas}");
    }

    private function obtenerMateriasDelGrupo($grupoId, $gestion, $materiasActivas)
    {
        $materiasAsignadasIds = AsignacionAcademica::query()
            ->where('grupo_id', $grupoId)
            ->where('gestion', $gestion)
            ->where('estado', 'activa')
            ->pluck('materia_id')
            ->unique()
            ->values();

        if ($materiasAsignadasIds->count() > 0) {
            return Materia::whereIn('id', $materiasAsignadasIds)
                ->where('activo', true)
                ->orderBy('id')
                ->get();
        }

        return $materiasActivas;
    }

    private function generarNotas($index): array
    {
        /*
         * Distribución aproximada:
         * 70% aprobados
         * 30% reprobados
         */

        $debeReprobar = ($index % 10) < 3;

        if ($debeReprobar) {
            return [
                random_int(35, 58),
                random_int(38, 62),
                random_int(30, 59),
            ];
        }

        return [
            random_int(60, 95),
            random_int(62, 98),
            random_int(60, 100),
        ];
    }

    private function calcularPromedio($p1, $p2, $ef, $materia): float
    {
        $porcentajeP1 = $materia->porcentaje_p1 ?? 30;
        $porcentajeP2 = $materia->porcentaje_p2 ?? 30;
        $porcentajeEF = $materia->porcentaje_ef ?? 40;

        $promedio = (
            ($p1 * $porcentajeP1) +
            ($p2 * $porcentajeP2) +
            ($ef * $porcentajeEF)
        ) / 100;

        return round($promedio, 2);
    }

    private function guardarCalificacion($examenId, $tipo, $nota): void
    {
        Calificacion::updateOrCreate(
            [
                'examen_id' => $examenId,
                'tipo' => $tipo,
            ],
            [
                'nota' => $nota,
            ]
        );
    }
}