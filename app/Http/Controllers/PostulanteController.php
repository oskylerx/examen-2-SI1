<?php

namespace App\Http\Controllers;

use App\Mail\CredencialesPostulanteMail;
use App\Models\Carrera;
use App\Models\DocumentoPostulante;
use App\Models\Grupo;
use App\Models\Postulante;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PostulanteController extends Controller
{
    public function index()
    {
        $postulantes = Postulante::with([
            'user',
            'grupo',
            'primeraOpcionCarrera',
            'segundaOpcionCarrera',
        ])
            ->orderBy('id', 'desc')
            ->get();

        $carreras = Carrera::orderBy('nombre')->get();
        $grupos = Grupo::orderBy('nombre')->get();

        return view('postulantes', compact('postulantes', 'carreras', 'grupos'));
    }

    public function create()
    {
        $postulantes = Postulante::with([
            'user',
            'grupo',
            'primeraOpcionCarrera',
            'segundaOpcionCarrera',
        ])
            ->orderBy('id', 'desc')
            ->get();

        $carreras = Carrera::orderBy('nombre')->get();
        $grupos = Grupo::orderBy('nombre')->get();

        return view('postulantes', [
            'modo' => 'crear',
            'postulantes' => $postulantes,
            'carreras' => $carreras,
            'grupos' => $grupos,
        ]);
    }

    public function store(Request $request)
    {
        $this->validarDatosCreacion($request);

        DB::transaction(function () use ($request) {
            $user = $this->guardarUsuario($request);

            $this->guardarPostulante($request, $user);
        });

        return redirect()
            ->route('postulantes.index')
            ->with('success', 'Postulante registrado correctamente.');
    }

    public function show(Postulante $postulante)
    {
        $postulante->load([
            'user',
            'grupo',
            'primeraOpcionCarrera',
            'segundaOpcionCarrera',
        ]);

        $postulantes = Postulante::with([
            'user',
            'grupo',
            'primeraOpcionCarrera',
            'segundaOpcionCarrera',
        ])
            ->orderBy('id', 'desc')
            ->get();

        $carreras = Carrera::orderBy('nombre')->get();
        $grupos = Grupo::orderBy('nombre')->get();

        return view('postulantes', [
            'modo' => 'detalle',
            'postulante' => $postulante,
            'postulantes' => $postulantes,
            'carreras' => $carreras,
            'grupos' => $grupos,
        ]);
    }

    public function edit(Postulante $postulante)
    {
        $postulante->load([
            'user',
            'grupo',
            'primeraOpcionCarrera',
            'segundaOpcionCarrera',
        ]);

        $postulantes = Postulante::with([
            'user',
            'grupo',
            'primeraOpcionCarrera',
            'segundaOpcionCarrera',
        ])
            ->orderBy('id', 'desc')
            ->get();

        $carreras = Carrera::orderBy('nombre')->get();
        $grupos = Grupo::orderBy('nombre')->get();

        return view('postulantes', [
            'modo' => 'editar',
            'postulante' => $postulante,
            'postulantes' => $postulantes,
            'carreras' => $carreras,
            'grupos' => $grupos,
        ]);
    }

    public function update(Request $request, Postulante $postulante)
    {
        $this->validarDatosActualizacion($request, $postulante);

        DB::transaction(function () use ($request, $postulante) {
            $this->actualizarUsuario($request, $postulante);

            $this->actualizarPostulante($request, $postulante);

            // $this->activarUsuarioSiAceptado($request, $postulante);

            if ($request->estado_inscripcion === 'aceptado') {
                if (! $this->tieneTodosDocumentosValidados($postulante)) {
                    throw ValidationException::withMessages([
                        'estado_inscripcion' => 'No se puede aceptar al postulante. Primero deben estar validados todos sus documentos.',
                    ]);
                }

                $this->activarUsuarioYEnviarCredenciales($postulante);
            } else {
                $postulante->user?->update([
                    'estado' => 'inactivo',
                ]);
            }
        });

        return redirect()
            ->route('postulantes.index')
            ->with('success', 'Postulante actualizado correctamente.');
    }

    public function destroy(Postulante $postulante)
    {
        DB::transaction(function () use ($postulante) {
            $user = $postulante->user;

            $postulante->delete();

            if ($user) {
                $user->delete();
            }
        });

        return redirect()
            ->route('postulantes.index')
            ->with('success', 'Postulante eliminado correctamente.');
    }

    private function validarDatosCreacion(Request $request): void
    {
        $request->validate([
            'ci' => 'required|string|max:20|unique:users,ci',
            'name' => 'required|string|max:100',
            'apellido' => 'required|string|max:100',
            'telefono' => 'nullable|string|max:30',
            'email' => 'required|email|max:150|unique:users,email',

            'primera_opcion_carrera_id' => 'required|exists:carrera,id',
            'segunda_opcion_carrera_id' => 'required|exists:carrera,id|different:primera_opcion_carrera_id',

            'fecha_nacimiento' => 'nullable|date',
            'genero' => 'nullable|in:masculino,femenino,otro',
            'direccion' => 'nullable|string|max:200',
            'colegio' => 'nullable|string|max:150',
            'ciudad' => 'nullable|string|max:100',
        ], [
            'ci.required' => 'El CI es obligatorio.',
            'ci.unique' => 'Ya existe un usuario con ese CI.',
            'name.required' => 'El nombre es obligatorio.',
            'apellido.required' => 'El apellido es obligatorio.',
            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'El correo no tiene un formato válido.',
            'email.unique' => 'Ya existe un usuario con ese correo.',
            'primera_opcion_carrera_id.required' => 'Debe seleccionar la primera opción de carrera.',
            'segunda_opcion_carrera_id.required' => 'Debe seleccionar la segunda opción de carrera.',
            'segunda_opcion_carrera_id.different' => 'La segunda opción debe ser diferente a la primera opción.',
        ]);
    }

    private function validarDatosActualizacion(Request $request, Postulante $postulante): void
    {
        $request->validate([
            'ci' => 'required|string|max:20|unique:users,ci,'.$postulante->user_id,
            'name' => 'required|string|max:100',
            'apellido' => 'required|string|max:100',
            'telefono' => 'nullable|string|max:30',
            'email' => 'required|email|max:150|unique:users,email,'.$postulante->user_id,

            'grupo_id' => 'nullable|exists:grupo,id',
            'primera_opcion_carrera_id' => 'required|exists:carrera,id',
            'segunda_opcion_carrera_id' => 'required|exists:carrera,id|different:primera_opcion_carrera_id',

            'fecha_nacimiento' => 'nullable|date',
            'genero' => 'nullable|in:masculino,femenino,otro',
            'direccion' => 'nullable|string|max:200',
            'colegio' => 'nullable|string|max:150',
            'ciudad' => 'nullable|string|max:100',

            'estado_inscripcion' => 'required|in:pendiente,observado,aceptado,rechazado',
            'observacion' => 'nullable|string|max:200',
        ], [
            'ci.required' => 'El CI es obligatorio.',
            'ci.unique' => 'Ya existe un usuario con ese CI.',
            'name.required' => 'El nombre es obligatorio.',
            'apellido.required' => 'El apellido es obligatorio.',
            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'El correo no tiene un formato válido.',
            'email.unique' => 'Ya existe un usuario con ese correo.',
            'segunda_opcion_carrera_id.different' => 'La segunda opción debe ser diferente a la primera opción.',
        ]);
    }

    private function guardarUsuario(Request $request): User
    {
        $rolPostulante = Rol::where('nombre', 'Postulante')->first();

        if (! $rolPostulante) {
            abort(500, 'No existe el rol Postulante en la tabla roles.');
        }

        return User::create([
            'rol_id' => $rolPostulante->id,
            'username' => $this->generarUsernamePostulante(),
            'ci' => $request->ci,
            'name' => $request->name,
            'apellido' => $request->apellido,
            'telefono' => $request->telefono,
            'email' => $request->email,
            'password' => Hash::make('1234'),
            'estado' => 'inactivo',
        ]);
    }

    private function guardarPostulante(Request $request, User $user): Postulante
    {
        return Postulante::create([
            'user_id' => $user->id,
            'grupo_id' => null,
            'primera_opcion_carrera_id' => $request->primera_opcion_carrera_id,
            'segunda_opcion_carrera_id' => $request->segunda_opcion_carrera_id,
            'fecha_nacimiento' => $request->fecha_nacimiento,
            'genero' => $request->genero,
            'direccion' => $request->direccion,
            'colegio' => $request->colegio,
            'ciudad' => $request->ciudad,
            'fecha_registro' => now()->toDateString(),
            'estado_inscripcion' => 'pendiente',
            'observacion' => null,
        ]);
    }

    private function actualizarUsuario(Request $request, Postulante $postulante): void
    {
        $postulante->user->update([
            'ci' => $request->ci,
            'name' => $request->name,
            'apellido' => $request->apellido,
            'telefono' => $request->telefono,
            'email' => $request->email,
        ]);
    }

    private function actualizarPostulante(Request $request, Postulante $postulante): void
    {
        $postulante->update([
            'grupo_id' => $request->grupo_id,
            'primera_opcion_carrera_id' => $request->primera_opcion_carrera_id,
            'segunda_opcion_carrera_id' => $request->segunda_opcion_carrera_id,
            'fecha_nacimiento' => $request->fecha_nacimiento,
            'genero' => $request->genero,
            'direccion' => $request->direccion,
            'colegio' => $request->colegio,
            'ciudad' => $request->ciudad,
            'estado_inscripcion' => $request->estado_inscripcion,
            'observacion' => $request->observacion,
        ]);
    }

    // private function activarUsuarioSiAceptado(Request $request, Postulante $postulante): void
    // {
    //     if ($request->estado_inscripcion === 'aceptado') {
    //         $postulante->user->update([
    //             'estado' => 'activo',
    //         ]);
    //     }

    //     if (in_array($request->estado_inscripcion, ['pendiente', 'observado', 'rechazado'])) {
    //         $postulante->user->update([
    //             'estado' => 'inactivo',
    //         ]);
    //     }
    // }
    private function generarPasswordTemporal(): string
    {
        return 'CUP-'.strtoupper(Str::random(6));
    }

    private function activarUsuarioSiAceptado(Request $request, Postulante $postulante): void
    {
        $user = $postulante->user;

        if (! $user) {
            return;
        }

        if ($request->estado_inscripcion === 'aceptado') {

            if ($user->estado !== 'activo') {
                $passwordTemporal = $this->generarPasswordTemporal();

                $user->update([
                    'estado' => 'activo',
                    'password' => Hash::make($passwordTemporal),
                ]);

                if ($user->email) {
                    Mail::to($user->email)->send(
                        new CredencialesPostulanteMail($postulante, $passwordTemporal)
                    );
                }
            }

            return;
        }

        if (in_array($request->estado_inscripcion, ['pendiente', 'observado', 'rechazado'])) {
            $user->update([
                'estado' => 'inactivo',
            ]);
        }
    }

    private function generarUsernamePostulante(): string
    {
        $anio = now()->format('y');
        $gestion = '1';

        $prefijo = $anio.$gestion;

        $ultimoUsuario = User::where('username', 'like', $prefijo.'%')
            ->orderBy('username', 'desc')
            ->first();

        if (! $ultimoUsuario) {
            $correlativo = 1;
        } else {
            $ultimoCorrelativo = (int) substr($ultimoUsuario->username, -5);
            $correlativo = $ultimoCorrelativo + 1;
        }

        return $prefijo.str_pad($correlativo, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Actualiza el estado de inscripción directamente desde la tabla y ejecuta
     * las reglas de activación/desactivación del usuario vinculado.
     */
    // public function updateEstado(Request $request, $id)
    // {
    //     $request->validate([
    //         'estado_inscripcion' => 'required|in:pendiente,observado,aceptado,rechazado',
    //     ]);

    //     // Cargamos el postulante junto con su usuario para poder modificarlo
    //     $postulante = Postulante::with('user')->findOrFail($id);

    //     DB::transaction(function () use ($request, $postulante) {
    //         // Actualizamos la inscripción
    //         $postulante->update([
    //             'estado_inscripcion' => $request->estado_inscripcion,
    //         ]);

    //         // Evaluamos la regla: 'aceptado' -> activo, los demás -> inactivo
    //         $this->activarUsuarioSiAceptado($request, $postulante);
    //     });

    //     return back()->with('success', 'Estados del postulante y del usuario actualizados correctamente.');
    // }

    public function documentos(Postulante $postulante)
    {
        $postulante->load([
            'user',
            'documentos',
            'primeraOpcionCarrera',
            'segundaOpcionCarrera',
        ]);

        return view('postulantes-documentos', compact('postulante'));
    }

    public function actualizarDocumento(Request $request, Postulante $postulante, DocumentoPostulante $documento)
    {
        if ($documento->postulante_id !== $postulante->id) {
            abort(404);
        }

        $request->validate([
            'estado' => 'required|in:pendiente,validado,observado,rechazado',
            'otro' => 'nullable|string|max:1000',
        ]);

        $documento->update([
            'estado' => $request->estado,
            'otro' => $request->otro,
        ]);

        $this->aceptarPostulanteSiDocumentosValidados($postulante);

        return redirect()
            ->route('postulantes.documentos', $postulante)
            ->with('success', 'Documento actualizado correctamente.');
    }

    public function updateEstado(Request $request, Postulante $postulante)
    {
        $request->validate([
            'estado_inscripcion' => 'required|in:pendiente,observado,aceptado,rechazado',
        ]);

        if ($request->estado_inscripcion === 'aceptado' && ! $this->tieneTodosDocumentosValidados($postulante)) {
            return redirect()
                ->route('postulantes.index')
                ->withErrors('No se puede aceptar al postulante. Primero deben estar validados todos sus documentos.');
        }

        $postulante->update([
            'estado_inscripcion' => $request->estado_inscripcion,
        ]);

        if ($request->estado_inscripcion === 'aceptado') {
            $this->activarUsuarioYEnviarCredenciales($postulante);
        } else {
            $postulante->user?->update([
                'estado' => 'inactivo',
            ]);
        }

        return redirect()
            ->route('postulantes.index')
            ->with('success', 'Estado actualizado correctamente.');
    }

    private function aceptarPostulanteSiDocumentosValidados(Postulante $postulante): void
    {
        $postulante->load('documentos', 'user');

        if (! $this->tieneTodosDocumentosValidados($postulante)) {
            return;
        }

        if ($postulante->estado_inscripcion !== 'aceptado') {
            $postulante->update([
                'estado_inscripcion' => 'aceptado',
            ]);
        }

        $this->activarUsuarioYEnviarCredenciales($postulante);
    }

    private function tieneTodosDocumentosValidados(Postulante $postulante): bool
    {
        $tiposRequeridos = [
            'comprobante_pago',
            'titulo_bachiller',
            'cedula_identidad',
            'boletin_sexto',
        ];

        $documentos = $postulante->documentos()
            ->whereIn('tipo', $tiposRequeridos)
            ->get();

        if ($documentos->count() < count($tiposRequeridos)) {
            return false;
        }

        return $documentos->every(function ($documento) {
            return $documento->estado === 'validado';
        });
    }

    private function activarUsuarioYEnviarCredenciales(Postulante $postulante): void
    {
        $postulante->load('user');

        $user = $postulante->user;

        if (! $user) {
            return;
        }

        if ($user->estado === 'activo') {
            return;
        }

        $passwordTemporal = $this->generarPasswordTemporal();

        $user->update([
            'estado' => 'activo',
            'password' => Hash::make($passwordTemporal),
        ]);

        if ($user->email) {
            Mail::to($user->email)->send(
                new CredencialesPostulanteMail($postulante, $passwordTemporal)
            );
        }
    }
}
