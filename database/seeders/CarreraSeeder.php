<?php

namespace Database\Seeders;

use App\Models\Carrera;
use Illuminate\Database\Seeder;

class CarreraSeeder extends Seeder
{
    public function run(): void
    {
        Carrera::updateOrCreate(
            ['codigo_carrera' => '187-4'],
            [
                'nombre' => 'Ingeniería en Sistemas',
                'descripcion' => 'Carrera orientada al desarrollo de software, sistemas de información y soluciones tecnológicas.',
                'cupos' => 0,
                'nota_min_ingreso' => 60.00,
                'estado' => 'activa',
            ]
        );

        Carrera::updateOrCreate(
            ['codigo_carrera' => '187-3'],
            [
                'nombre' => 'Ingeniería Informática',
                'descripcion' => 'Carrera orientada a la informática, programación, infraestructura tecnológica y gestión de sistemas.',
                'cupos' => 0,
                'nota_min_ingreso' => 60.00,
                'estado' => 'activa',
            ]
        );

        Carrera::updateOrCreate(
            ['codigo_carrera' => '187-5'],
            [
                'nombre' => 'Ingeniería en Redes y Telecomunicaciones',
                'descripcion' => 'Carrera orientada a redes, telecomunicaciones, conectividad e infraestructura de comunicación.',
                'cupos' => 0,
                'nota_min_ingreso' => 60.00,
                'estado' => 'activa',
            ]
        );

        Carrera::updateOrCreate(
            ['codigo_carrera' => '187-6'],
            [
                'nombre' => 'Ingeniería Robótica',
                'descripcion' => 'Carrera orientada a robótica, automatización, electrónica y sistemas inteligentes.',
                'cupos' => 0,
                'nota_min_ingreso' => 60.00,
                'estado' => 'activa',
            ]
        );
    }
}