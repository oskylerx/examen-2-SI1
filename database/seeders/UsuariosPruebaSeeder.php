<?php

namespace Database\Seeders;

use App\Models\Administrador;
use App\Models\Coordinador;
use App\Models\Docente;
use App\Models\Postulante;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Database\Seeder;

class UsuariosPruebaSeeder extends Seeder
{
    public function run(): void
    {
        $rolAdministrador = Rol::firstWhere('nombre', 'Administrador');
        $rolCoordinador = Rol::firstWhere('nombre', 'Coordinador');
        $rolDocente = Rol::firstWhere('nombre', 'Docente');
        $rolPostulante = Rol::firstWhere('nombre', 'Postulante');

        if (!$rolAdministrador || !$rolCoordinador || !$rolDocente || !$rolPostulante) {
            throw new \Exception('Primero debes ejecutar RolSeeder. Faltan roles en la tabla roles.');
        }

        /*
        |--------------------------------------------------------------------------
        | Usuario Administrador
        |--------------------------------------------------------------------------
        */
        $admin = User::updateOrCreate(
            ['username' => 'admin'],
            [
                'rol_id' => $rolAdministrador->id,
                'ci' => '12345678',
                'name' => 'Administrador',
                'apellido' => 'Principal',
                'telefono' => '70000000',
                'email' => 'admin@cup.com',
                'password' => '1234',
                'estado' => 'activo',
            ]
        );

        Administrador::updateOrCreate(
            ['user_id' => $admin->id],
            []
        );

        /*
        |--------------------------------------------------------------------------
        | Usuario Coordinador
        |--------------------------------------------------------------------------
        | Formato username:
        | 26 = año
        | 0001 = correlativo
        | Resultado: 260001
        */
        $coordinador = User::updateOrCreate(
            ['username' => '260001'],
            [
                'rol_id' => $rolCoordinador->id,
                'ci' => '260001',
                'name' => 'Carlos',
                'apellido' => 'Coordinador',
                'telefono' => '70000001',
                'email' => 'coordinador@cup.com',
                'password' => '1234',
                'estado' => 'activo',
            ]
        );

        Coordinador::updateOrCreate(
            ['user_id' => $coordinador->id],
            [
                'especialidad' => 'Coordinación Académica CUP',
                'activo' => true,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Usuario Docente
        |--------------------------------------------------------------------------
        | Formato username:
        | 1 = tipo docente
        | 26 = año
        | 0001 = correlativo
        | Resultado: 1260001
        */
        $docente = User::updateOrCreate(
            ['username' => '1260001'],
            [
                'rol_id' => $rolDocente->id,
                'ci' => '1260001',
                'name' => 'Luis',
                'apellido' => 'Docente',
                'telefono' => '70000002',
                'email' => 'docente@cup.com',
                'password' => '12345678',
                'estado' => 'activo',
            ]
        );

        Docente::updateOrCreate(
            ['user_id' => $docente->id],
            [
                'profesion' => 'Licenciado en Matemática',
                'especialidad' => 'Matemática',
                'maestria' => null,
                'diplomado' => 'Educación Superior',
                'estado_validacion' => 'aceptado',
                'observacion' => 'Docente registrado para pruebas del sistema.',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Usuario Postulante / Estudiante
        |--------------------------------------------------------------------------
        | Formato username:
        | 26 = año
        | 1 = gestión
        | 00001 = correlativo
        | Resultado: 26100001
        */
        $postulante = User::updateOrCreate(
            ['username' => '26100001'],
            [
                'rol_id' => $rolPostulante->id,
                'ci' => '87654321',
                'name' => 'María',
                'apellido' => 'Postulante',
                'telefono' => '70000003',
                'email' => 'postulante@cup.com',
                'password' => '1234',
                'estado' => 'activo',
            ]
        );

        Postulante::updateOrCreate(
            ['user_id' => $postulante->id],
            [
                'fecha_nacimiento' => '2007-05-15',
                'genero' => 'femenino',
                'direccion' => 'Av. Principal #123',
                'colegio' => 'Colegio Nacional Bolivia',
                'ciudad' => 'Santa Cruz',
                'titulo_bachiller' => true,
                'otros_documentos' => 'Cédula de Identidad, Boletín de 6to de Secundaria, Comprobante de pago.',
                'fecha_registro' => now()->toDateString(),
                'estado_inscripcion' => 'aceptado',
            ]
        );
    }
}