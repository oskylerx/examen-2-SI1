<?php

namespace Database\Seeders;

use App\Models\Coordinador;
use App\Models\Docente;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CoordinadorDocentesSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Obtener roles necesarios
        $rolCoordinador = Rol::firstOrCreate(['nombre' => 'Coordinador']);
        $rolDocente = Rol::firstOrCreate(['nombre' => 'Docente']);

        // 2. Crear usuario y perfil del Coordinador
        $coordinadorUser = User::updateOrCreate(
            ['username' => '260002'],
            [
                'rol_id' => $rolCoordinador->id,
                'ci' => '260002',
                'name' => 'Roberto',
                'apellido' => 'Gómez Coordinador',
                'telefono' => '71111111',
                'email' => 'roberto.coordinador@cup.com',
                'password' => Hash::make('1234'),
                'estado' => 'activo',
            ]
        );

        $coordinador = Coordinador::updateOrCreate(
            ['user_id' => $coordinadorUser->id],
            [
                'especialidad' => 'Supervisión de Docentes y Calificaciones',
                'activo' => true,
            ]
        );

        // 3. Definir datos para los 5 docentes
        $docentesData = [
            [
                'ci' => '8000001',
                'name' => 'Alejandro',
                'apellido' => 'Morales Física',
                'email' => 'alejandro.morales@cup.com',
                'profesion' => 'Licenciado en Física',
                'especialidad' => 'Física',
            ],
            [
                'ci' => '8000002',
                'name' => 'Beatriz',
                'apellido' => 'Luna Matemática',
                'email' => 'beatriz.luna@cup.com',
                'profesion' => 'Licenciada en Matemática',
                'especialidad' => 'Matemática',
            ],
            [
                'ci' => '8000003',
                'name' => 'Carlos',
                'apellido' => 'Sosa Cómputo',
                'email' => 'carlos.sosa@cup.com',
                'profesion' => 'Ingeniero de Sistemas',
                'especialidad' => 'Computación',
            ],
            [
                'ci' => '8000004',
                'name' => 'Diana',
                'apellido' => 'Ramos Inglés',
                'email' => 'diana.ramos@cup.com',
                'profesion' => 'Licenciada en Idiomas',
                'especialidad' => 'Inglés',
            ],
            [
                'ci' => '8000005',
                'name' => 'Eduardo',
                'apellido' => 'Castillo Álgebra',
                'email' => 'eduardo.castillo@cup.com',
                'profesion' => 'Licenciado en Matemática',
                'especialidad' => 'Matemática',
            ],
        ];

        // 4. Crear los 5 docentes y asignarlos al Coordinador
        foreach ($docentesData as $index => $data) {
            $username = 'DOC' . str_pad($index + 10, 5, '0', STR_PAD_LEFT); // DOC00010, DOC00011, etc.

            $docenteUser = User::updateOrCreate(
                ['username' => $username],
                [
                    'rol_id' => $rolDocente->id,
                    'ci' => $data['ci'],
                    'name' => $data['name'],
                    'apellido' => $data['apellido'],
                    'telefono' => '7222222' . $index,
                    'email' => $data['email'],
                    'password' => Hash::make('1234'),
                    'estado' => 'activo',
                ]
            );

            Docente::updateOrCreate(
                ['user_id' => $docenteUser->id],
                [
                    'coordinador_id' => $coordinador->id,
                    'profesion' => $data['profesion'],
                    'especialidad' => $data['especialidad'],
                    'maestria' => 'Educación Superior',
                    'diplomado' => 'Didáctica Universitaria',
                    'estado_validacion' => 'aceptado',
                    'observacion' => 'Docente creado mediante seeder de prueba.',
                ]
            );
        }
    }
}
