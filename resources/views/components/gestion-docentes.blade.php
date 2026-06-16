<?php

use Livewire\Component;
use App\Models\User;
use App\Models\Rol;
use App\Models\Docente;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\CredencialesDocenteMail;

new class extends Component
{
    public string $modo = 'lista';

    public $docentes = [];

    public $docente_id = null;
    public $user_id = null;

    public $ci = '';
    public $name = '';
    public $apellido = '';
    public $telefono = '';
    public $email = '';

    public $profesion = '';
    public $especialidad = ''; // Ahora es requerido por esquema
    public $maestria = '';
    public $diplomado = '';
    public $estado_validacion = 'pendiente'; // Valor por defecto en esquema
    public $observacion = '';

    public $buscar = '';

    public function mount()
    {
        $this->index();
    }

    public function index()
    {
        $this->modo = 'lista';
        $this->docentes = Docente::with('user')
            ->latest()
            ->get();
    }

    public function create()
    {
        $this->limpiarFormulario();
        $this->modo = 'crear';
    }

    public function validarCI($ci): bool
    {
        return preg_match('/^[0-9]{5,20}$/', $ci);
    }

    public function save()
    {
        // Validación adaptada estrictamente a las longitudes y nulidades de tu esquema
        $this->validate([
            'ci' => 'required|string|max:20|unique:users,ci',
            'name' => 'required|string|max:100',
            'apellido' => 'required|string|max:100',
            'telefono' => 'nullable|string|max:30',
            'email' => 'required|email|max:150|unique:users,email',
            'profesion' => 'required|string|max:100', // Ajustado a max 100
            'especialidad' => 'required|string|max:100', // REQUERIDO según tu esquema
            'maestria' => 'nullable|string|max:150',
            'diplomado' => 'nullable|string|max:150',
            'estado_validacion' => 'required|in:pendiente,aceptado,rechazado', // Enum exacto
            'observacion' => 'nullable|string',
        ], [
            'ci.required' => 'El CI es obligatorio.',
            'ci.unique' => 'Este CI ya se encuentra registrado.',
            'name.required' => 'El nombre es obligatorio.',
            'apellido.required' => 'El apellido es obligatorio.',
            'email.required' => 'El correo es obligatorio.',
            'email.unique' => 'Este correo ya está en uso.',
            'profesion.required' => 'La profesión es obligatoria.',
            'especialidad.required' => 'La especialidad es obligatoria.',
        ]);

        DB::transaction(function () {
            $rolDocente = Rol::where('nombre', 'Docente')->first();
            
            if (!$rolDocente) {
                session()->flash('error', 'Error: No se encontró el rol "Docente" en la tabla roles.');
                return;
            }

            $username = 'D' . $this->ci;
            $passwordTemporal = '1234';

            // Si es 'aceptado' se guarda como 'activo', de lo contrario 'inactivo'
            $estadoUsuario = ($this->estado_validacion === 'aceptado') ? 'activo' : 'inactivo';

            $user = User::create([
                'rol_id' => $rolDocente->id,
                'username' => $username,
                'ci' => $this->ci,
                'name' => $this->name,
                'apellido' => $this->apellido,
                'telefono' => $this->telefono,
                'email' => $this->email,
                'password' => Hash::make($passwordTemporal),
                'estado' => $estadoUsuario,
            ]);

            Docente::create([
                'user_id' => $user->id,
                'coordinador_id' => null, // Según tu esquema es nullable
                'profesion' => $this->profesion,
                'especialidad' => $this->especialidad,
                'maestria' => $this->maestria,
                'diplomado' => $this->diplomado,
                'estado_validacion' => $this->estado_validacion,
                'observacion' => $this->observacion,
            ]);

            // Solo envía correo si se crea directamente como 'aceptado'
            if ($this->estado_validacion === 'aceptado') {
                Mail::to($this->email)->send(new CredencialesDocenteMail(
                    $this->name, 
                    $username, 
                    $passwordTemporal
                ));
            }
        });

        session()->flash('success', 'Docente registrado correctamente.');
        $this->index();
    }

    public function updateEstado($id, $nuevoEstado)
    {
        // Validamos que el estado pertenezca al enum antes de procesar
        if (!in_array($nuevoEstado, ['pendiente', 'aceptado', 'rechazado'])) {
            session()->flash('error', 'Estado no permitido.');
            return;
        }

        $docente = Docente::with('user')->findOrFail($id);
        
        DB::transaction(function () use ($docente, $nuevoEstado) {
            $docente->update([
                'estado_validacion' => $nuevoEstado
            ]);

            if ($docente->user) {
                $isAceptado = ($nuevoEstado === 'aceptado');
                
                $docente->user->update([
                    'estado' => $isAceptado ? 'activo' : 'inactivo'
                ]);

                // Si pasa a aceptado desde la tabla, se envía el correo
                if ($isAceptado) {
                    $passwordTemporal = '1234';
                    Mail::to($docente->user->email)->send(new CredencialesDocenteMail(
                        $docente->user->name, 
                        $docente->user->username, 
                        $passwordTemporal
                    ));
                }
            }
        });

        $this->index();
        session()->flash('success', 'Estado actualizado. Si fue aceptado, las credenciales se enviaron al Gmail.');
    }

    private function limpiarFormulario()
    {
        $this->reset([
            'docente_id',
            'user_id',
            'ci',
            'name',
            'apellido',
            'telefono',
            'email',
            'profesion',
            'especialidad',
            'maestria',
            'diplomado',
            'observacion',
            'buscar',
        ]);

        $this->estado_validacion = 'pendiente';
        $this->resetErrorBag();
    }
};
?>

<div class="gestion-container">
    <style>
        .gestion-container {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .main-title {
            color: #2c3e50;
            font-size: 28px;
            margin-bottom: 20px;
            border-bottom: 2px solid #ecf0f1;
            padding-bottom: 10px;
        }
        .section-title {
            color: #34495e;
            font-size: 20px;
            margin-bottom: 15px;
        }
        .subsection-title {
            color: #7f8c8d;
            font-size: 14px;
            text-transform: uppercase;
            margin-top: 10px;
            margin-bottom: 15px;
            letter-spacing: 0.5px;
            border-bottom: 1px dashed #cbd5e1;
            padding-bottom: 5px;
        }
        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .alert-success { background-color: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
        .alert-danger { background-color: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }

        .nav-actions {
            margin-bottom: 25px;
            background: #f8f9fa;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #e9ecef;
            display: flex;
            gap: 10px;
        }
        .btn-menu {
            background: transparent;
            border: none;
            color: #4a5568;
            font-weight: 600;
            padding: 6px 12px;
            cursor: pointer;
            border-radius: 4px;
            font-size: 14px;
        }
        .btn-menu.active {
            background: #3498db;
            color: white;
        }

        .form-panel {
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
            padding: 25px;
            margin-bottom: 30px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
        }
        .form-group-full { grid-column: 1 / -1; }
        .form-group label {
            font-size: 13px;
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 6px;
        }
        .form-control, select, textarea {
            padding: 8px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 14px;
            background-color: #fff;
            color: #334155;
        }
        .form-control:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #3498db;
        }
        .text-danger {
            color: #e74c3c;
            font-size: 12px;
            margin-top: 4px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
        }
        .btn-primary { background: #2ecc71; color: #fff; }
        .btn-primary:hover { background: #27ae60; }

        .table-container {
            overflow-x: auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }
        .custom-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            text-align: left;
        }
        .custom-table th {
            background-color: #f1f5f9;
            color: #475569;
            padding: 12px;
            font-weight: 600;
            border-bottom: 2px solid #cbd5e1;
        }
        .custom-table td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
            color: #334155;
            vertical-align: middle;
        }
        .custom-table tr:hover { background-color: #f8fafc; }

        .select-status-inline {
            padding: 4px 8px;
            font-size: 13px;
            font-weight: 500;
            border-radius: 4px;
            border: 1px solid #cbd5e1;
            cursor: pointer;
        }
        .status-pendiente { background-color: #fef3c7; color: #d97706; border-color: #fcd34d; }
        .status-aceptado { background-color: #d1e7dd; color: #0f5132; border-color: #badbcc; }
        .status-rechazado { background-color: #f8d7da; color: #842029; border-color: #f5c2c7; }
        
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-activo { background-color: #d1e7dd; color: #0f5132; }
        .badge-inactivo { background-color: #f8d7da; color: #842029; }
    </style>

    <h1 class="main-title">CU04 - Gestionar Docente</h1>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="nav-actions">
        <button type="button" class="btn-menu {{ $modo === 'lista' ? 'active' : '' }}" wire:click="index">
            Lista de Docentes
        </button>
        <button type="button" class="btn-menu {{ $modo === 'crear' ? 'active' : '' }}" wire:click="create">
            + Nuevo Docente
        </button>
    </div>

    {{-- VISTA: LISTA --}}
    @if($modo === 'lista')
        <h2 class="section-title">Lista de docentes</h2>

        <div class="table-container">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Nombre completo</th>
                        <th>CI</th>
                        <th>Correo</th>
                        <th>Profesión</th>
                        <th>Especialidad</th>
                        <th style="min-width: 140px;">Estado validación</th>
                        <th>Estado usuario</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($docentes as $docente)
                        <tr>
                            <td>{{ $docente->id }}</td>
                            <td><strong>{{ $docente->user->username ?? 'N/A' }}</strong></td>
                            <td>{{ $docente->user->name ?? '' }} {{ $docente->user->apellido ?? '' }}</td>
                            <td>{{ $docente->user->ci ?? '' }}</td>
                            <td>{{ $docente->user->email ?? '' }}</td>
                            <td>{{ $docente->profesion }}</td>
                            <td>{{ $docente->especialidad }}</td>
                            
                            <td>
                                <select class="select-status-inline status-{{ $docente->estado_validacion }}"
                                        wire:change="updateEstado({{ $docente->id }}, $event.target.value)">
                                    <option value="pendiente" {{ $docente->estado_validacion === 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                                    <option value="aceptado" {{ $docente->estado_validacion === 'aceptado' ? 'selected' : '' }}>Aceptado</option>
                                    <option value="rechazado" {{ $docente->estado_validacion === 'rechazado' ? 'selected' : '' }}>Rechazado</option>
                                </select>
                            </td>

                            <td>
                                @if(($docente->user->estado ?? '') === 'activo')
                                    <span class="badge badge-activo">Activo</span>
                                @else
                                    <span class="badge badge-inactivo">Inactivo</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="text-align: center; color: #94a3b8; padding: 25px;">
                                No hay docentes registrados en el sistema.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif

    {{-- VISTA: CREAR --}}
    @if($modo === 'crear')
        <div class="form-panel">
            <h2 class="section-title" style="margin-bottom: 20px;">Registrar Nuevo Docente</h2>

            <form wire:submit.prevent="save">
                
                <h3 class="subsection-title">Datos Personales y de Cuenta</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label>CI:</label>
                        <input type="text" wire:model="ci" class="form-control" placeholder="Ej: 8432111">
                        @error('ci') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label>Nombre:</label>
                        <input type="text" wire:model="name" class="form-control">
                        @error('name') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label>Apellido:</label>
                        <input type="text" wire:model="apellido" class="form-control">
                        @error('apellido') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label>Teléfono:</label>
                        <input type="text" wire:model="telefono" class="form-control">
                        @error('telefono') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label>Correo Electrónico:</label>
                        <input type="email" wire:model="email" class="form-control" placeholder="ejemplo@correo.com">
                        @error('email') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>

                <h3 class="subsection-title">Información Profesional e Institucional</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Profesión / Título:</label>
                        <input type="text" wire:model="profesion" class="form-control" placeholder="Ej: Ingeniero de Sistemas">
                        @error('profesion') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label>Especialidad:</label>
                        <input type="text" wire:model="especialidad" class="form-control" placeholder="Ej: Inteligencia Artificial">
                        @error('especialidad') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label>Maestría:</label>
                        <input type="text" wire:model="maestria" class="form-control">
                        @error('maestria') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label>Diplomado:</label>
                        <input type="text" wire:model="diplomado" class="form-control">
                        @error('diplomado') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label>Estado Inicial de Validación:</label>
                        <select wire:model="estado_validacion">
                            <option value="pendiente">Pendiente</option>
                            <option value="aceptado">Aceptado</option>
                            <option value="rechazado">Rechazado</option>
                        </select>
                        @error('estado_validacion') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group form-group-full">
                        <label>Observaciones de Registro:</label>
                        <textarea wire:model="observacion" rows="2" placeholder="Notas sobre la validación inicial..."></textarea>
                        @error('observacion') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div style="margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">
                        Registrar Docente
                    </button>
                    <button type="button" class="btn" style="background:#e2e8f0; margin-left:10px;" wire:click="index">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    @endif
</div>