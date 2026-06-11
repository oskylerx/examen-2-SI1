<?php

namespace Database\Seeders;

use App\Models\Administrador;
use App\Models\Carrera;
use App\Models\Coordinador;
use App\Models\Docente;
use App\Models\DocumentoPostulante;
use App\Models\Pago;
use App\Models\Postulante;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Database\Seeder;

class UsuariosPruebaSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Roles
        |--------------------------------------------------------------------------
        */
        $rolAdministrador = Rol::firstWhere('nombre', 'Administrador');
        $rolCoordinador = Rol::firstWhere('nombre', 'Coordinador');
        $rolDocente = Rol::firstWhere('nombre', 'Docente');
        $rolPostulante = Rol::firstWhere('nombre', 'Postulante');

        if (!$rolAdministrador || !$rolCoordinador || !$rolDocente || !$rolPostulante) {
            throw new \Exception('Primero debes ejecutar RolSeeder. Faltan roles en la tabla roles.');
        }

        /*
        |--------------------------------------------------------------------------
        | Carreras
        |--------------------------------------------------------------------------
        */
        $carreraSistemas = Carrera::updateOrCreate(
            ['codigo_carrera' => '187-4'],
            [
                'nombre' => 'Ingeniería en Sistemas',
                'descripcion' => 'Carrera orientada al desarrollo de software, sistemas de información y soluciones tecnológicas.',
                'cupos' => 40,
                'nota_min_ingreso' => 60.00,
                'estado' => 'activa',
            ]
        );

        $carreraInformatica = Carrera::updateOrCreate(
            ['codigo_carrera' => '187-3'],
            [
                'nombre' => 'Ingeniería Informática',
                'descripcion' => 'Carrera orientada a la informática, programación, infraestructura tecnológica y gestión de sistemas.',
                'cupos' => 35,
                'nota_min_ingreso' => 60.00,
                'estado' => 'activa',
            ]
        );

        Carrera::updateOrCreate(
            ['codigo_carrera' => '187-5'],
            [
                'nombre' => 'Ingeniería en Redes y Telecomunicaciones',
                'descripcion' => 'Carrera orientada a redes, telecomunicaciones, conectividad e infraestructura de comunicación.',
                'cupos' => 30,
                'nota_min_ingreso' => 60.00,
                'estado' => 'activa',
            ]
        );

        Carrera::updateOrCreate(
            ['codigo_carrera' => '187-6'],
            [
                'nombre' => 'Ingeniería Robótica',
                'descripcion' => 'Carrera orientada a robótica, automatización, electrónica y sistemas inteligentes.',
                'cupos' => 25,
                'nota_min_ingreso' => 60.00,
                'estado' => 'activa',
            ]
        );

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
                'password' => '1234',
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
        $postulanteUser = User::updateOrCreate(
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

        $postulante = Postulante::updateOrCreate(
            ['user_id' => $postulanteUser->id],
            [
                'fecha_nacimiento' => '2007-05-15',
                'genero' => 'femenino',
                'direccion' => 'Av. Principal #123',
                'colegio' => 'Colegio Nacional Bolivia',
                'ciudad' => 'Santa Cruz',

                'primera_opcion_carrera_id' => $carreraSistemas->id,
                'segunda_opcion_carrera_id' => $carreraInformatica->id,
                'fecha_registro' => now()->toDateString(),
                'estado_inscripcion' => 'aceptado',
                'observacion' => 'Postulante registrado para pruebas del sistema.',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Pago CUP del postulante
        |--------------------------------------------------------------------------
        */
        Pago::updateOrCreate(
            ['postulante_id' => $postulante->id],
            [
                'concepto' => 'Pago CUP',
                'fecha_pago' => now()->toDateString(),
                'monto' => 700.00,
                'estado' => 'pagado',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Documentos del postulante
        |--------------------------------------------------------------------------
        */
        DocumentoPostulante::updateOrCreate(
            [
                'postulante_id' => $postulante->id,
                'tipo' => 'titulo_bachiller',
            ],
            [
                'nombre' => 'Título de Bachiller',
                'archivo' => 'documentos/postulantes/26100001/titulo_bachiller.pdf',
                'otro' => null,
                'estado' => 'validado',
                'fecha_subida' => now()->toDateString(),
            ]
        );

        DocumentoPostulante::updateOrCreate(
            [
                'postulante_id' => $postulante->id,
                'tipo' => 'cedula_identidad',
            ],
            [
                'nombre' => 'Cédula de Identidad',
                'archivo' => 'documentos/postulantes/26100001/cedula_identidad.pdf',
                'otro' => null,
                'estado' => 'validado',
                'fecha_subida' => now()->toDateString(),
            ]
        );

        DocumentoPostulante::updateOrCreate(
            [
                'postulante_id' => $postulante->id,
                'tipo' => 'boletin_sexto',
            ],
            [
                'nombre' => 'Boletín de 6to de Secundaria',
                'archivo' => 'documentos/postulantes/26100001/boletin_sexto.pdf',
                'otro' => null,
                'estado' => 'validado',
                'fecha_subida' => now()->toDateString(),
            ]
        );

        DocumentoPostulante::updateOrCreate(
            [
                'postulante_id' => $postulante->id,
                'tipo' => 'comprobante_pago',
            ],
            [
                'nombre' => 'Comprobante de Pago CUP',
                'archivo' => 'documentos/postulantes/26100001/comprobante_pago.pdf',
                'otro' => null,
                'estado' => 'validado',
                'fecha_subida' => now()->toDateString(),
            ]
        );
    }
}