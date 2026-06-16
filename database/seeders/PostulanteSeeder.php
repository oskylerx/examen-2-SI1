<?php

namespace Database\Seeders;

use App\Models\Carrera;
use App\Models\DocumentoPostulante;
use App\Models\Grupo;
use App\Models\Pago;
use App\Models\Postulante;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PostulanteSeeder extends Seeder
{
    public function run(): void
    {
        $cantidadPostulantes = 300;
        $gestion = '2026-1';
        $cupoMaximoGrupo = 70;

        $rolPostulanteId = DB::table('roles')
            ->whereRaw('LOWER(nombre) = ?', ['postulante'])
            ->value('id');

        if (! $rolPostulanteId) {
            throw new \Exception('No existe el rol Postulante en la tabla roles.');
        }

        $carreras = Carrera::orderBy('id')->get();

        if ($carreras->isEmpty()) {
            throw new \Exception('No existen carreras registradas. Primero ejecuta el seeder de carreras.');
        }

        $this->asegurarGrupos($carreras, $cantidadPostulantes, $gestion, $cupoMaximoGrupo);

        $grupos = Grupo::with('carrera')
            ->where('activo', true)
            ->where('gestion', $gestion)
            ->orderBy('id')
            ->get();

        if ($grupos->isEmpty()) {
            $grupos = Grupo::with('carrera')
                ->where('activo', true)
                ->orderBy('id')
                ->get();
        }

        if ($grupos->isEmpty()) {
            throw new \Exception('No existen grupos activos para asignar postulantes.');
        }

        $nombresMasculinos = [
            'Carlos', 'Luis', 'Juan', 'Pedro', 'Miguel', 'Jorge', 'Diego', 'Fernando',
            'Andres', 'Marco', 'Raul', 'Daniel', 'Jose', 'Sergio', 'Hugo',
        ];

        $nombresFemeninos = [
            'Maria', 'Ana', 'Carla', 'Lucia', 'Gabriela', 'Daniela', 'Valeria', 'Sofia',
            'Camila', 'Fernanda', 'Paola', 'Andrea', 'Laura', 'Mariana', 'Natalia',
        ];

        $apellidos = [
            'Rodriguez', 'Gonzales', 'Mamani', 'Vargas', 'Rojas', 'Lopez', 'Perez',
            'Flores', 'Gutierrez', 'Sanchez', 'Morales', 'Ortiz', 'Aguilera',
            'Rivero', 'Suarez', 'Castro', 'Romero', 'Torrez', 'Mendoza', 'Salazar',
        ];

        $ciudades = [
            'Santa Cruz', 'Montero', 'Warnes', 'La Guardia', 'Cotoca',
            'El Torno', 'Portachuelo', 'Yapacani',
        ];

        $colegios = [
            'Colegio Nacional Florida',
            'Colegio La Salle',
            'Colegio San Agustin',
            'Colegio Nacional Junin',
            'Colegio Don Bosco',
            'Colegio Santa Ana',
            'Colegio 24 de Septiembre',
            'Colegio Nacional Bolivar',
        ];

        DB::transaction(function () use (
            $cantidadPostulantes,
            $rolPostulanteId,
            $grupos,
            $carreras,
            $nombresMasculinos,
            $nombresFemeninos,
            $apellidos,
            $ciudades,
            $colegios
        ) {
            for ($index = 0; $index < $cantidadPostulantes; $index++) {
                $numero = $index + 1;

                $username = '261'.str_pad($numero, 5, '0', STR_PAD_LEFT);
                $ci = (string) (9100000 + $numero);

                $genero = $numero % 2 === 0 ? 'femenino' : 'masculino';

                $nombre = $genero === 'femenino'
                    ? $nombresFemeninos[$index % count($nombresFemeninos)]
                    : $nombresMasculinos[$index % count($nombresMasculinos)];

                $apellidoPaterno = $apellidos[$index % count($apellidos)];
                $apellidoMaterno = $apellidos[($index + 5) % count($apellidos)];
                $apellidoCompleto = $apellidoPaterno.' '.$apellidoMaterno;

                $indiceGrupo = intdiv($index, 70);
                $grupo = $grupos[$indiceGrupo] ?? $grupos->last();

                $primeraCarreraId = $grupo->carrera_id ?: $carreras[$index % $carreras->count()]->id;

                $segundaCarrera = $carreras
                    ->where('id', '!=', $primeraCarreraId)
                    ->values()
                    ->get(0);

                $segundaCarreraId = $segundaCarrera?->id;

                $user = User::updateOrCreate(
                    [
                        'username' => $username,
                    ],
                    [
                        'rol_id' => $rolPostulanteId,
                        'ci' => $ci,
                        'name' => $nombre,
                        'apellido' => $apellidoCompleto,
                        'telefono' => '7'.str_pad((string) $numero, 7, '0', STR_PAD_LEFT),
                        'email' => strtolower($username).'@cup.test',
                        'password' => Hash::make('1234'),
                        'estado' => 'activo',
                    ]
                );

                $postulante = Postulante::updateOrCreate(
                    [
                        'user_id' => $user->id,
                    ],
                    [
                        'grupo_id' => $grupo->id,
                        'primera_opcion_carrera_id' => $primeraCarreraId,
                        'segunda_opcion_carrera_id' => $segundaCarreraId,
                        'fecha_nacimiento' => now()->subYears(18)->subDays($numero)->format('Y-m-d'),
                        'genero' => $genero,
                        'direccion' => 'Barrio Universitario, calle '.$numero,
                        'colegio' => $colegios[$index % count($colegios)],
                        'ciudad' => $ciudades[$index % count($ciudades)],
                        'fecha_registro' => now()->format('Y-m-d'),
                        'estado_inscripcion' => 'aceptado',
                        'observacion' => 'Postulante generado automáticamente con documentación completa.',
                    ]
                );

                Pago::updateOrCreate(
                    [
                        'postulante_id' => $postulante->id,
                        'concepto' => 'Pago CUP',
                    ],
                    [
                        'fecha_pago' => now()->format('Y-m-d'),
                        'monto' => 700.00,
                        'estado' => 'pagado',
                    ]
                );

                $this->crearDocumento(
                    $postulante->id,
                    $username,
                    'Título de Bachiller',
                    'titulo_bachiller',
                    'titulo_bachiller.pdf'
                );

                $this->crearDocumento(
                    $postulante->id,
                    $username,
                    'Cédula de Identidad',
                    'cedula_identidad',
                    'cedula_identidad.pdf'
                );

                $this->crearDocumento(
                    $postulante->id,
                    $username,
                    'Boletín de Sexto de Secundaria',
                    'boletin_sexto',
                    'boletin_sexto.pdf'
                );

                $this->crearDocumento(
                    $postulante->id,
                    $username,
                    'Comprobante de Pago',
                    'comprobante_pago',
                    'comprobante_pago.pdf'
                );
            }
        });
    }

    private function crearDocumento($postulanteId, $username, $nombre, $tipo, $archivo): void
    {
        DocumentoPostulante::updateOrCreate(
            [
                'postulante_id' => $postulanteId,
                'tipo' => $tipo,
            ],
            [
                'nombre' => $nombre,
                'archivo' => 'documentos/postulantes/'.$username.'/'.$archivo,
                'otro' => null,
                'estado' => 'validado',
                'fecha_subida' => now()->format('Y-m-d'),
            ]
        );
    }

    private function asegurarGrupos($carreras, $cantidadPostulantes, $gestion, $cupoMaximoGrupo): void
    {
        $gruposNecesarios = (int) ceil($cantidadPostulantes / $cupoMaximoGrupo);

        $gruposActuales = Grupo::where('activo', true)
            ->where('gestion', $gestion)
            ->count();

        if ($gruposActuales >= $gruposNecesarios) {
            return;
        }

        $faltantes = $gruposNecesarios - $gruposActuales;

        for ($i = 1; $i <= $faltantes; $i++) {
            $numeroGrupo = $gruposActuales + $i;
            $letra = chr(64 + $numeroGrupo);

            $carrera = $carreras[($numeroGrupo - 1) % $carreras->count()];

            Grupo::updateOrCreate(
                [
                    'nombre' => 'Grupo '.$letra.' - '.($carrera->codigo_carrera ?? $carrera->id),
                    'gestion' => $gestion,
                ],
                [
                    'docente_id' => null,
                    'coordinador_id' => null,
                    'carrera_id' => $carrera->id,
                    'cupos_maximo' => $cupoMaximoGrupo,
                    'activo' => true,
                ]
            );
        }
    }
}
