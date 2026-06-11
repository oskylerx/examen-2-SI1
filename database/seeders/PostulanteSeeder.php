<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Rol;
use App\Models\Carrera;
use App\Models\Postulante;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PostulanteSeeder extends Seeder
{
    public function run(): void
    {
        $rolPostulante = Rol::where('nombre', 'Postulante')->first();

        if (!$rolPostulante) {
            $this->command->error('No existe el rol Postulante.');
            return;
        }

        $sistemas = Carrera::where('codigo_carrera', '187-4')->first();
        $informatica = Carrera::where('codigo_carrera', '187-3')->first();
        $redes = Carrera::where('codigo_carrera', '187-5')->first();
        $robotica = Carrera::where('codigo_carrera', '187-6')->first();

        if (!$sistemas || !$informatica || !$redes || !$robotica) {
            $this->command->error('Faltan carreras registradas.');
            return;
        }

        $postulantes = [
            [
                'ci' => '90000001',
                'name' => 'María',
                'apellido' => 'Gutiérrez Rojas',
                'telefono' => '70000001',
                'email' => 'maria.gutierrez@example.com',
                'fecha_nacimiento' => '2006-03-15',
                'genero' => 'femenino',
                'direccion' => 'Barrio Equipetrol',
                'colegio' => 'Colegio Nacional Florida',
                'ciudad' => 'Santa Cruz',
                'primera' => $sistemas->id,
                'segunda' => $informatica->id,
            ],
            [
                'ci' => '90000002',
                'name' => 'Juan',
                'apellido' => 'Pérez Vargas',
                'telefono' => '70000002',
                'email' => 'juan.perez@example.com',
                'fecha_nacimiento' => '2005-11-22',
                'genero' => 'masculino',
                'direccion' => 'Av. Alemana',
                'colegio' => 'Colegio Don Bosco',
                'ciudad' => 'Santa Cruz',
                'primera' => $informatica->id,
                'segunda' => $sistemas->id,
            ],
            [
                'ci' => '90000003',
                'name' => 'Camila',
                'apellido' => 'Suárez Mendoza',
                'telefono' => '70000003',
                'email' => 'camila.suarez@example.com',
                'fecha_nacimiento' => '2006-07-09',
                'genero' => 'femenino',
                'direccion' => 'Plan 3000',
                'colegio' => 'Colegio San Agustín',
                'ciudad' => 'Santa Cruz',
                'primera' => $redes->id,
                'segunda' => $informatica->id,
            ],
            [
                'ci' => '90000004',
                'name' => 'Luis',
                'apellido' => 'Ribera Ortiz',
                'telefono' => '70000004',
                'email' => 'luis.ribera@example.com',
                'fecha_nacimiento' => '2005-05-30',
                'genero' => 'masculino',
                'direccion' => 'Zona Norte',
                'colegio' => 'Colegio La Salle',
                'ciudad' => 'Santa Cruz',
                'primera' => $robotica->id,
                'segunda' => $sistemas->id,
            ],
            [
                'ci' => '90000005',
                'name' => 'Andrea',
                'apellido' => 'Molina Céspedes',
                'telefono' => '70000005',
                'email' => 'andrea.molina@example.com',
                'fecha_nacimiento' => '2006-01-18',
                'genero' => 'femenino',
                'direccion' => 'Villa Primero de Mayo',
                'colegio' => 'Colegio Nacional Junín',
                'ciudad' => 'Santa Cruz',
                'primera' => $sistemas->id,
                'segunda' => $redes->id,
            ],
            [
                'ci' => '90000006',
                'name' => 'Carlos',
                'apellido' => 'Fernández López',
                'telefono' => '70000006',
                'email' => 'carlos.fernandez@example.com',
                'fecha_nacimiento' => '2005-09-12',
                'genero' => 'masculino',
                'direccion' => 'Av. Beni',
                'colegio' => 'Colegio Cristo Rey',
                'ciudad' => 'Santa Cruz',
                'primera' => $informatica->id,
                'segunda' => $robotica->id,
            ],
            [
                'ci' => '90000007',
                'name' => 'Valeria',
                'apellido' => 'Flores Arancibia',
                'telefono' => '70000007',
                'email' => 'valeria.flores@example.com',
                'fecha_nacimiento' => '2006-12-01',
                'genero' => 'femenino',
                'direccion' => 'Doble vía La Guardia',
                'colegio' => 'Colegio Santa Ana',
                'ciudad' => 'Santa Cruz',
                'primera' => $redes->id,
                'segunda' => $sistemas->id,
            ],
            [
                'ci' => '90000008',
                'name' => 'Diego',
                'apellido' => 'Salvatierra Roca',
                'telefono' => '70000008',
                'email' => 'diego.salvatierra@example.com',
                'fecha_nacimiento' => '2005-04-25',
                'genero' => 'masculino',
                'direccion' => 'Av. Virgen de Cotoca',
                'colegio' => 'Colegio Salesiano',
                'ciudad' => 'Santa Cruz',
                'primera' => $robotica->id,
                'segunda' => $redes->id,
            ],
            [
                'ci' => '90000009',
                'name' => 'Sofía',
                'apellido' => 'Núñez Pereira',
                'telefono' => '70000009',
                'email' => 'sofia.nunez@example.com',
                'fecha_nacimiento' => '2006-10-10',
                'genero' => 'femenino',
                'direccion' => 'Barrio Hamacas',
                'colegio' => 'Colegio María Auxiliadora',
                'ciudad' => 'Santa Cruz',
                'primera' => $sistemas->id,
                'segunda' => $robotica->id,
            ],
            [
                'ci' => '90000010',
                'name' => 'Mateo',
                'apellido' => 'Castro Villarroel',
                'telefono' => '70000010',
                'email' => 'mateo.castro@example.com',
                'fecha_nacimiento' => '2005-08-14',
                'genero' => 'masculino',
                'direccion' => 'Av. Santos Dumont',
                'colegio' => 'Colegio Domingo Savio',
                'ciudad' => 'Santa Cruz',
                'primera' => $informatica->id,
                'segunda' => $redes->id,
            ],
        ];

        foreach ($postulantes as $index => $data) {
            $username = '261' . str_pad($index + 1, 5, '0', STR_PAD_LEFT);

            $user = User::create([
                'rol_id' => $rolPostulante->id,
                'username' => $username,
                'ci' => $data['ci'],
                'name' => $data['name'],
                'apellido' => $data['apellido'],
                'telefono' => $data['telefono'],
                'email' => $data['email'],
                'password' => Hash::make('1234'),
                'estado' => 'inactivo',
            ]);

            Postulante::create([
                'user_id' => $user->id,
                'grupo_id' => null,
                'primera_opcion_carrera_id' => $data['primera'],
                'segunda_opcion_carrera_id' => $data['segunda'],
                'fecha_nacimiento' => $data['fecha_nacimiento'],
                'genero' => $data['genero'],
                'direccion' => $data['direccion'],
                'colegio' => $data['colegio'],
                'ciudad' => $data['ciudad'],
                'fecha_registro' => now()->toDateString(),
                'estado_inscripcion' => 'pendiente',
                'observacion' => null,
            ]);
        }
    }
}