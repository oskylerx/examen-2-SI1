<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Rol;
use App\Models\Carrera;
use App\Models\Postulante;
use App\Models\DocumentoPostulante;

new class extends Component {
    use WithFileUploads;

    public int $paso = 1;

    public ?int $postulante_id = null;

    public $ci;
    public $name;
    public $apellido;
    public $telefono;
    public $email;

    public $fecha_nacimiento;
    public $genero;
    public $direccion;
    public $colegio;
    public $ciudad;

    public $primera_opcion_carrera_id;
    public $segunda_opcion_carrera_id;

    public $comprobante_pago;

    public $titulo_bachiller;
    public $cedula_identidad;
    public $boletin_sexto;
    public $otro_documento;

    public function guardarDatosBasicos()
    {
        $this->validate(
            [
                'ci' => 'required|string|max:20|unique:users,ci',
                'name' => 'required|string|max:100',
                'apellido' => 'required|string|max:100',
                'telefono' => 'nullable|string|max:30',
                'email' => 'required|email|max:150|unique:users,email',

                'fecha_nacimiento' => 'nullable|date',
                'genero' => 'nullable|in:masculino,femenino,otro',
                'direccion' => 'nullable|string|max:200',
                'colegio' => 'nullable|string|max:150',
                'ciudad' => 'nullable|string|max:100',

                'primera_opcion_carrera_id' => 'required|exists:carrera,id',
                'segunda_opcion_carrera_id' => 'required|exists:carrera,id|different:primera_opcion_carrera_id',
            ],
            [
                'ci.required' => 'El CI es obligatorio.',
                'ci.unique' => 'Ya existe una inscripción registrada con este CI.',
                'name.required' => 'El nombre es obligatorio.',
                'apellido.required' => 'El apellido es obligatorio.',
                'email.required' => 'El correo es obligatorio.',
                'email.email' => 'El correo no tiene un formato válido.',
                'email.unique' => 'Ya existe una inscripción registrada con este correo.',
                'primera_opcion_carrera_id.required' => 'Debe seleccionar la primera opción de carrera.',
                'segunda_opcion_carrera_id.required' => 'Debe seleccionar la segunda opción de carrera.',
                'segunda_opcion_carrera_id.different' => 'La segunda opción debe ser diferente a la primera opción.',
            ],
        );

        DB::transaction(function () {
            $rolPostulante = Rol::where('nombre', 'Postulante')->first();

            if (!$rolPostulante) {
                throw new Exception('No existe el rol Postulante en la tabla roles.');
            }

            $user = User::create([
                'rol_id' => $rolPostulante->id,
                'username' => $this->generarUsernamePostulante(),
                'ci' => $this->ci,
                'name' => $this->name,
                'apellido' => $this->apellido,
                'telefono' => $this->telefono,
                'email' => $this->email,
                'password' => Hash::make('1234'),
                'estado' => 'inactivo',
            ]);

            $postulante = Postulante::create([
                'user_id' => $user->id,
                'grupo_id' => null,
                'primera_opcion_carrera_id' => $this->primera_opcion_carrera_id,
                'segunda_opcion_carrera_id' => $this->segunda_opcion_carrera_id,
                'fecha_nacimiento' => $this->fecha_nacimiento,
                'genero' => $this->genero,
                'direccion' => $this->direccion,
                'colegio' => $this->colegio,
                'ciudad' => $this->ciudad,
                'fecha_registro' => now()->toDateString(),
                'estado_inscripcion' => 'pendiente',
                'observacion' => null,
            ]);

            $this->postulante_id = $postulante->id;
        });

        $this->paso = 2;
    }

    public function subirComprobante()
    {
        $this->validate(
            [
                'comprobante_pago' => 'required|file|mimes:jpg,jpeg,png,pdf|max:4096',
            ],
            [
                'comprobante_pago.required' => 'Debe subir el comprobante de pago.',
                'comprobante_pago.mimes' => 'El comprobante debe ser JPG, PNG o PDF.',
                'comprobante_pago.max' => 'El comprobante no debe pesar más de 4MB.',
            ],
        );

        $postulante = Postulante::findOrFail($this->postulante_id);

        $ruta = $this->comprobante_pago->store('postulantes/' . $postulante->id . '/documentos', 'public');

        DocumentoPostulante::updateOrCreate(
            [
                'postulante_id' => $postulante->id,
                'tipo' => 'comprobante_pago',
            ],
            [
                'nombre' => 'Comprobante de Pago CUP',
                'archivo' => $ruta,
                'otro' => null,
                'estado' => 'validado',
                'fecha_subida' => now()->toDateString(),
            ],
        );

        $this->paso = 3;
    }

    public function subirDocumentos()
    {
        $this->validate(
            [
                'titulo_bachiller' => 'required|file|mimes:jpg,jpeg,png,pdf|max:20000',
                'cedula_identidad' => 'required|file|mimes:jpg,jpeg,png,pdf|max:20000',
                'boletin_sexto' => 'required|file|mimes:jpg,jpeg,png,pdf|max:20000',
                'otro_documento' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:20000',
            ],
            [
                'titulo_bachiller.required' => 'Debe subir el Título de Bachiller.',
                'cedula_identidad.required' => 'Debe subir la Cédula de Identidad.',
                'boletin_sexto.required' => 'Debe subir el Boletín de 6to de Secundaria.',
            ],
        );

        $postulante = Postulante::findOrFail($this->postulante_id);

        $this->guardarDocumento($postulante, $this->titulo_bachiller, 'Título de Bachiller', 'titulo_bachiller');

        $this->guardarDocumento($postulante, $this->cedula_identidad, 'Cédula de Identidad', 'cedula_identidad');

        $this->guardarDocumento($postulante, $this->boletin_sexto, 'Boletín de 6to de Secundaria', 'boletin_sexto');

        if ($this->otro_documento) {
            $this->guardarDocumento($postulante, $this->otro_documento, 'Otro documento', 'otro');
        }

        $this->paso = 4;
    }

    private function guardarDocumento(Postulante $postulante, $archivo, string $nombre, string $tipo): void
    {
        $ruta = $archivo->store('postulantes/' . $postulante->id . '/documentos', 'public');

        DocumentoPostulante::updateOrCreate(
            [
                'postulante_id' => $postulante->id,
                'tipo' => $tipo,
            ],
            [
                'nombre' => $nombre,
                'archivo' => $ruta,
                'otro' => null,
                'estado' => 'pendiente',
                'fecha_subida' => now()->toDateString(),
            ],
        );
    }

    private function generarUsernamePostulante(): string
    {
        $anio = now()->format('y');
        $gestion = '1';

        $prefijo = $anio . $gestion;

        $ultimoUsuario = User::where('username', 'like', $prefijo . '%')
            ->orderBy('username', 'desc')
            ->first();

        if (!$ultimoUsuario) {
            $correlativo = 1;
        } else {
            $ultimoCorrelativo = (int) substr($ultimoUsuario->username, -5);
            $correlativo = $ultimoCorrelativo + 1;
        }

        return $prefijo . str_pad($correlativo, 5, '0', STR_PAD_LEFT);
    }

    public function volverAlFormulario()
    {
        $this->paso = 1;
    }

    public function irAPago()
    {
        $this->paso = 2;
    }

    public function irADocumentos()
    {
        $this->paso = 3;
    }
};

$carreras = Carrera::orderBy('nombre')->get();

?>

<div class="preinscripcion-box">
    <a href="{{ route('login') }}" class="login-button">
        Iniciar sesión
    </a>

    <style>
        .preinscripcion-box {
            font-family: Arial, sans-serif;
        }

        .steps {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .step {
            flex: 1;
            padding: 10px;
            border-radius: 8px;
            text-align: center;
            background: #e5e7eb;
            color: #374151;
            font-size: 14px;
        }

        .step.active {
            background: #2563eb;
            color: white;
            font-weight: bold;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 5px;
            font-weight: bold;
            color: #374151;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 10px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
        }

        .form-full {
            grid-column: 1 / 3;
        }

        .btn {
            background: #2563eb;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 15px;
        }

        .btn:hover {
            background: #1d4ed8;
        }

        .btn-secondary {
            background: #6b7280;
        }

        .btn-secondary:hover {
            background: #4b5563;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .qr-box {
            border: 2px dashed #9ca3af;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            background: #f9fafb;
            margin: 20px 0;
        }

        .qr-placeholder {
            width: 220px;
            height: 220px;
            margin: 0 auto;
            background: white;
            border: 1px solid #d1d5db;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #6b7280;
            font-weight: bold;
        }

        .success-box {
            background: #dcfce7;
            border: 1px solid #86efac;
            color: #166534;
            padding: 20px;
            border-radius: 12px;
        }

        @media (max-width: 700px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-full {
                grid-column: 1;
            }

            .steps {
                flex-direction: column;
            }
        }

        .login-button {
            position: fixed;
            top: 18px;
            right: 22px;
            z-index: 1000;

            background: white;
            color: #1d4ed8;
            padding: 10px 18px;
            border-radius: 999px;
            text-decoration: none;
            font-weight: bold;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.15);
            border: 1px solid #dbeafe;
        }

        .login-button:hover {
            background: #eff6ff;
        }
    </style>

    <div class="steps">
        <div class="step {{ $paso === 1 ? 'active' : '' }}">1. Datos</div>
        <div class="step {{ $paso === 2 ? 'active' : '' }}">2. Pago QR</div>
        <div class="step {{ $paso === 3 ? 'active' : '' }}">3. Documentos</div>
        <div class="step {{ $paso === 4 ? 'active' : '' }}">4. Finalizado</div>
    </div>

    @if ($errors->any())
        <div class="alert-error">
            <strong>Corrige los siguientes errores:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($paso === 1)
        <h2>Formulario de preinscripción</h2>

        <p>
            Complete sus datos personales y seleccione su primera y segunda opción de carrera.
        </p>

        <form wire:submit.prevent="guardarDatosBasicos">
            <div class="form-grid">

                <div class="form-group">
                    <label>CI</label>
                    <input type="text" wire:model="ci">
                </div>

                <div class="form-group">
                    <label>Teléfono</label>
                    <input type="text" wire:model="telefono">
                </div>

                <div class="form-group">
                    <label>Nombre</label>
                    <input type="text" wire:model="name">
                </div>

                <div class="form-group">
                    <label>Apellido</label>
                    <input type="text" wire:model="apellido">
                </div>

                <div class="form-group">
                    <label>Correo electrónico</label>
                    <input type="email" wire:model="email">
                </div>

                <div class="form-group">
                    <label>Fecha de nacimiento</label>
                    <input type="date" wire:model="fecha_nacimiento">
                </div>

                <div class="form-group">
                    <label>Género</label>
                    <select wire:model="genero">
                        <option value="">Seleccione...</option>
                        <option value="masculino">Masculino</option>
                        <option value="femenino">Femenino</option>
                        <option value="otro">Otro</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Ciudad</label>
                    <input type="text" wire:model="ciudad">
                </div>

                <div class="form-group form-full">
                    <label>Dirección</label>
                    <input type="text" wire:model="direccion">
                </div>

                <div class="form-group form-full">
                    <label>Colegio</label>
                    <input type="text" wire:model="colegio">
                </div>

                <div class="form-group">
                    <label>Primera opción de carrera</label>
                    <select wire:model="primera_opcion_carrera_id">
                        <option value="">Seleccione...</option>
                        @foreach (\App\Models\Carrera::orderBy('nombre')->get() as $carrera)
                            <option value="{{ $carrera->id }}">
                                {{ $carrera->codigo_carrera }} - {{ $carrera->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label>Segunda opción de carrera</label>
                    <select wire:model="segunda_opcion_carrera_id">
                        <option value="">Seleccione...</option>
                        @foreach (\App\Models\Carrera::orderBy('nombre')->get() as $carrera)
                            <option value="{{ $carrera->id }}">
                                {{ $carrera->codigo_carrera }} - {{ $carrera->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group form-full">
                    <button type="submit" class="btn" wire:loading.attr="disabled">
                        Generar QR de pago
                    </button>

                    <div wire:loading wire:target="guardarDatosBasicos">
                        Registrando datos...
                    </div>
                </div>

            </div>
        </form>
    @endif

    @if ($paso === 2)
        <h2>Pago CUP</h2>

        <p>
            Su preinscripción fue registrada. Ahora realice el pago correspondiente y suba el comprobante.
        </p>

        <p><strong>Monto:</strong> 700 Bs</p>

        <div class="qr-box">
            <h3>QR de pago</h3>

            <div class="qr-placeholder">
                QR CUP<br>700 Bs
            </div>

            <p>
                Escanee el QR desde su aplicación bancaria y realice el pago.
            </p>
        </div>

        <form wire:submit.prevent="subirComprobante">

            <div class="form-group">
                <label>Subir comprobante de pago</label>
                <input type="file" wire:model="comprobante_pago">
            </div>

            <br>

            <div wire:loading wire:target="comprobante_pago">
                Subiendo comprobante...
            </div>

            <button type="submit" class="btn" wire:loading.attr="disabled">
                Enviar comprobante
            </button>
        </form>
    @endif

    @if ($paso === 3)
        <h2>Documentos requeridos</h2>

        <p>
            Su comprobante fue registrado como validado. Ahora suba los documentos requeridos.
        </p>

        <form wire:submit.prevent="subirDocumentos">

            <div class="form-grid">

                <div class="form-group">
                    <label>Título de Bachiller</label>
                    <input type="file" wire:model="titulo_bachiller">
                </div>

                <div class="form-group">
                    <label>Cédula de Identidad</label>
                    <input type="file" wire:model="cedula_identidad">
                </div>

                <div class="form-group">
                    <label>Boletín de 6to de Secundaria</label>
                    <input type="file" wire:model="boletin_sexto">
                </div>

                <div class="form-group">
                    <label>Otro documento opcional</label>
                    <input type="file" wire:model="otro_documento">
                </div>

                <div class="form-group form-full">
                    <div wire:loading>
                        Subiendo documentos...
                    </div>

                    <button type="submit" class="btn" wire:loading.attr="disabled">
                        Finalizar preinscripción
                    </button>
                </div>

            </div>
        </form>
    @endif

    @if ($paso === 4)
        <div class="success-box">
            <h2>Preinscripción finalizada</h2>

            <p>
                Su preinscripción fue registrada correctamente.
            </p>

            <p>
                El administrador o coordinador revisará sus documentos.
                Cuando su inscripción sea aceptada, su usuario será habilitado en el sistema.
            </p>

            <p>
                Revise su correo electrónico para futuras notificaciones.
            </p>
        </div>
    @endif

</div>
