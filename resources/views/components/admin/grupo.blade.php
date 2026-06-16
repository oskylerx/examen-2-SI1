<?php

use App\Models\Carrera;
use App\Models\Grupo;
use App\Models\Postulante;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;


new class extends Component
{
    use WithPagination;

    public $busqueda = '';
    public $estado = '';
    public $gestionFiltro = '2026-1';
    public $filtroCarrera = '';
    public $porPagina = 10;

    public $cupoMaximoCalculo = 70;

    public $modal = false;
    public $grupoId = null;

    public $nombre = '';
    public $carrera_id = '';
    public $gestion = '2026-1';
    public $cupos_maximo = 70;
    public $activo = true;

    public function updated($property)
    {
        if (in_array($property, [
            'busqueda',
            'estado',
            'gestionFiltro',
            'filtroCarrera',
            'porPagina',
        ])) {
            $this->resetPage();
        }
    }

    public function rules()
    {
        return [
            'nombre' => ['required', 'string', 'max:100'],
            'carrera_id' => ['required', Rule::exists((new Carrera)->getTable(), 'id')],
            'gestion' => ['required', 'string', 'max:50'],
            'cupos_maximo' => ['required', 'integer', 'min:1', 'max:70'],
            'activo' => ['required'],
        ];
    }

    private function postulantesValidos()
    {
        return Postulante::query()
            ->whereIn('estado_inscripcion', [
                'aprobado',
                'aceptado',
                'habilitado',
            ])
            ->when($this->filtroCarrera, function ($query) {
                $query->where('primera_opcion_carrera_id', $this->filtroCarrera);
            });
    }

    public function limpiarFiltros()
    {
        $this->busqueda = '';
        $this->estado = '';
        $this->filtroCarrera = '';
        $this->gestionFiltro = '2026-1';
        $this->porPagina = 10;

        $this->resetPage();
    }

    public function abrirCrear()
    {
        $this->resetValidation();

        $this->grupoId = null;
        $this->nombre = '';
        $this->carrera_id = '';
        $this->gestion = $this->gestionFiltro ?: '2026-1';
        $this->cupos_maximo = 70;
        $this->activo = true;

        $this->modal = true;
    }

    public function editar($id)
    {
        $grupo = Grupo::findOrFail($id);

        $this->resetValidation();

        $this->grupoId = $grupo->id;
        $this->nombre = $grupo->nombre;
        $this->carrera_id = $grupo->carrera_id;
        $this->gestion = $grupo->gestion;
        $this->cupos_maximo = $grupo->cupos_maximo;
        $this->activo = (bool) $grupo->activo;

        $this->modal = true;
    }

    public function guardar()
    {
        $data = $this->validate();

        $data['activo'] = (bool) $this->activo;

        if ($this->grupoId) {
            Grupo::findOrFail($this->grupoId)->update($data);
            session()->flash('mensaje', 'Grupo actualizado correctamente.');
        } else {
            Grupo::create($data);
            session()->flash('mensaje', 'Grupo creado correctamente.');
        }

        $this->cerrarModal();
        $this->resetPage();
    }

    public function cerrarModal()
    {
        $this->modal = false;
        $this->resetValidation();
    }

    public function cambiarEstado($id)
    {
        $grupo = Grupo::findOrFail($id);

        $grupo->update([
            'activo' => ! $grupo->activo,
        ]);

        session()->flash('mensaje', 'Estado del grupo actualizado.');
    }

    public function eliminar($id)
    {
        $grupo = Grupo::withCount('postulantes')->findOrFail($id);

        if ($grupo->postulantes_count > 0) {
            session()->flash('mensaje', 'No se puede eliminar un grupo con postulantes asignados.');
            return;
        }

        $grupo->delete();

        session()->flash('mensaje', 'Grupo eliminado correctamente.');
        $this->resetPage();
    }

    public function calcularGruposHabilitados()
    {
        $total = $this->postulantesValidos()->count();

        $gruposNecesarios = $total > 0
            ? (int) ceil($total / $this->cupoMaximoCalculo)
            : 0;

        session()->flash(
            'mensaje',
            "Con {$total} postulantes habilitados se necesitan {$gruposNecesarios} grupo(s)."
        );
    }

    public function generarGruposAutomaticos()
    {
        $carreras = Carrera::query()
            ->when($this->filtroCarrera, function ($query) {
                $query->where('id', $this->filtroCarrera);
            })
            ->orderBy('nombre')
            ->get();

        $creados = 0;

        foreach ($carreras as $carrera) {
            $total = Postulante::query()
                ->whereIn('estado_inscripcion', [
                    'aprobado',
                    'aceptado',
                    'habilitado',
                ])
                ->where('primera_opcion_carrera_id', $carrera->id)
                ->count();

            $necesarios = $total > 0
                ? (int) ceil($total / $this->cupoMaximoCalculo)
                : 0;

            for ($i = 1; $i <= $necesarios; $i++) {
                $nombreGrupo = $this->nombreGrupo($carrera, $i);

                $grupo = Grupo::firstOrCreate(
                    [
                        'nombre' => $nombreGrupo,
                        'gestion' => $this->gestionFiltro,
                        'carrera_id' => $carrera->id,
                    ],
                    [
                        'cupos_maximo' => $this->cupoMaximoCalculo,
                        'activo' => true,
                    ]
                );

                if ($grupo->wasRecentlyCreated) {
                    $creados++;
                }
            }
        }

        session()->flash('mensaje', "Proceso finalizado. Grupos nuevos creados: {$creados}.");
        $this->resetPage();
    }

    public function asignarPostulantesAGrupos()
    {
        $carreras = Carrera::query()
            ->when($this->filtroCarrera, function ($query) {
                $query->where('id', $this->filtroCarrera);
            })
            ->orderBy('nombre')
            ->get();

        $asignados = 0;

        foreach ($carreras as $carrera) {
            $postulantes = Postulante::query()
                ->whereIn('estado_inscripcion', [
                    'aprobado',
                    'aceptado',
                    'habilitado',
                ])
                ->where('primera_opcion_carrera_id', $carrera->id)
                ->orderBy('fecha_registro')
                ->get();

            $bloques = $postulantes->chunk($this->cupoMaximoCalculo);

            foreach ($bloques as $index => $bloque) {
                $numeroGrupo = $index + 1;

                $grupo = Grupo::firstOrCreate(
                    [
                        'nombre' => $this->nombreGrupo($carrera, $numeroGrupo),
                        'gestion' => $this->gestionFiltro,
                        'carrera_id' => $carrera->id,
                    ],
                    [
                        'cupos_maximo' => $this->cupoMaximoCalculo,
                        'activo' => true,
                    ]
                );

                Postulante::whereIn('id', $bloque->pluck('id'))
                    ->update([
                        'grupo_id' => $grupo->id,
                    ]);

                $asignados += $bloque->count();
            }
        }

        session()->flash('mensaje', "Postulantes asignados a grupos: {$asignados}.");
        $this->resetPage();
    }

    private function nombreGrupo($carrera, $numero)
    {
        $codigo = $carrera->codigo_carrera
            ?? $carrera->codigo
            ?? 'CAR';

        return 'Grupo ' . $this->letraGrupo($numero) . ' - ' . $codigo;
    }

    private function letraGrupo($numero)
    {
        $letras = '';

        while ($numero > 0) {
            $numero--;
            $letras = chr(65 + ($numero % 26)) . $letras;
            $numero = intdiv($numero, 26);
        }

        return $letras;
    }

    public function with()
    {
        $carreras = Carrera::orderBy('nombre')->get();

        $totalPostulantes = $this->postulantesValidos()->count();

        $gruposNecesarios = $totalPostulantes > 0
            ? (int) ceil($totalPostulantes / $this->cupoMaximoCalculo)
            : 0;

        $grupos = Grupo::query()
            ->with('carrera')
            ->withCount('postulantes')
            ->when($this->busqueda, function ($query) {
                $busqueda = '%' . strtolower($this->busqueda) . '%';

                $query->where(function ($q) use ($busqueda) {
                    $q->whereRaw('LOWER(nombre) LIKE ?', [$busqueda])
                        ->orWhereRaw('LOWER(gestion) LIKE ?', [$busqueda])
                        ->orWhereHas('carrera', function ($carreraQuery) use ($busqueda) {
                            $carreraQuery->whereRaw('LOWER(nombre) LIKE ?', [$busqueda]);
                        });
                });
            })
            ->when($this->estado !== '', function ($query) {
                $query->where('activo', $this->estado === 'activo');
            })
            ->when($this->gestionFiltro, function ($query) {
                $query->where('gestion', $this->gestionFiltro);
            })
            ->when($this->filtroCarrera, function ($query) {
                $query->where('carrera_id', $this->filtroCarrera);
            })
            ->orderBy('nombre')
            ->paginate($this->porPagina);

        return [
            'grupos' => $grupos,
            'carreras' => $carreras,
            'totalPostulantes' => $totalPostulantes,
            'gruposNecesarios' => $gruposNecesarios,
        ];
    }
};

?>

<div class="gestion-container">
    <style>
        /* Variables de Sistema de Diseño */
        .gestion-container {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            color: #1e293b;
            max-width: 1280px;
            margin: 0 auto;
            padding: 24px;
            background-color: #f8fafc;
            min-height: 100vh;
        }

        /* Tarjetas Estilo Elevado */
        .card {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
            padding: 24px;
            margin-bottom: 24px;
            transition: box-shadow 0.2s ease;
        }
        .card:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -4px rgba(0, 0, 0, 0.05);
        }

        /* Tipografía Exclusiva */
        h2 {
            color: #0f172a;
            font-size: 20px;
            font-weight: 700;
            margin: 0 0 8px 0;
            letter-spacing: -0.025em;
        }
        p {
            color: #64748b;
            font-size: 14px;
            margin: 0;
            line-height: 1.5;
        }

        /* Panel de Indicadores Estatísticos */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .stat {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }
        .stat span {
            color: #64748b;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 6px;
        }
        .stat strong {
            color: #3b82f6;
            font-size: 28px;
            font-weight: 800;
            letter-spacing: -0.05em;
        }

        /* Formularios y Filtros */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-top: 20px;
            margin-bottom: 20px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
        }
        .form-group.form-full {
            grid-column: 1 / -1;
        }
        .form-group label {
            font-size: 13px;
            font-weight: 600;
            color: #475569;
            margin-bottom: 6px;
        }
        .form-group input, .form-group select {
            padding: 10px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
            color: #334155;
            background-color: #ffffff;
            transition: all 0.2s ease;
        }
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.12);
        }

        /* Barras de Herramientas y Acciones */
        .actions-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            padding-top: 16px;
            border-top: 1px solid #f1f5f9;
        }
        .btn-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        /* Botones de Estilo Moderno */
        .btn {
            padding: 10px 18px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 1px solid transparent;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        /* Botón Primario */
        .btn:not(.btn-secondary):not(.btn-danger-text) {
            background: #3b82f6;
            color: #ffffff;
        }
        .btn:not(.btn-secondary):not(.btn-danger-text):hover {
            background: #2563eb;
        }
        /* Botón Secundario */
        .btn-secondary {
            background: #f1f5f9;
            border-color: #e2e8f0;
            color: #475569;
        }
        .btn-secondary:hover {
            background: #e2e8f0;
            color: #1e293b;
        }
        /* Botón de Texto Peligro */
        .btn-danger-text {
            background: transparent;
            color: #ef4444;
            padding: 10px 14px;
        }
        .btn-danger-text:hover {
            background: #fef2f2;
            border-radius: 8px;
        }

        /* Tabla de Datos Premium */
        .table-responsive {
            overflow-x: auto;
            margin-top: 16px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            background: #ffffff;
        }
        .table th {
            background: #f8fafc;
            color: #475569;
            font-weight: 600;
            padding: 14px 16px;
            border-bottom: 2px solid #e2e8f0;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.05em;
        }
        .table td {
            padding: 14px 16px;
            border-bottom: 1px solid #edf2f7;
            color: #334155;
        }
        .table tr:last-child td {
            border-bottom: none;
        }
        .table tr:hover td {
            background-color: #f8fafc;
        }

        /* Badges de Estado */
        .badge {
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        .badge-aprobado { background-color: #dcfce7; color: #15803d; }
        .badge-rechazado { background-color: #fee2e2; color: #b91c1c; }

        /* Estado de Carga e Indicadores */
        .loading-badge {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1d4ed8;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            animation: pulse 2s infinite;
        }
        .alert-message {
            background: #f0fdf4;
            border-left: 4px solid #22c55e;
            color: #166534;
            padding: 14px 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 14px;
            font-weight: 500;
        }

        /* Animación de Latido para Carga */
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: .6; }
        }
    </style>

    {{-- Encabezado Principal --}}
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div>
                <h2 style="font-size: 24px;">Gestión de Grupos</h2>
                <p>
                    Calcule los grupos habilitados, cree grupos por carrera y asigne postulantes respetando el máximo de 70 estudiantes.
                </p>
            </div>

            <div wire:loading class="loading-badge">
                <span>Actualizando datos...</span>
            </div>
        </div>
    </div>

    {{-- Notificaciones --}}
    @if (session()->has('mensaje'))
        <div class="alert-message">
            {{ session('mensaje') }}
        </div>
    @endif

    {{-- Módulos de Control Estadístico --}}
    <div class="grid">
        <div class="stat">
            <span>Postulantes habilitados</span>
            <strong>{{ $totalPostulantes }}</strong>
        </div>

        <div class="stat">
            <span>Grupos necesarios</span>
            <strong>{{ $gruposNecesarios }}</strong>
        </div>

        <div class="stat">
            <span>Cupo máximo</span>
            <strong>{{ $cupoMaximoCalculo }}</strong>
        </div>

        <div class="stat">
            <span>Gestión</span>
            <strong style="color: #10b981;">{{ $gestionFiltro }}</strong>
        </div>
    </div>

    {{-- Sección de Búsqueda y Parámetros --}}
    <div class="card">
        <h2>Parámetros de búsqueda</h2>

        <div class="form-grid">
            <div class="form-group">
                <label>Buscar grupo</label>
                <input type="text" wire:model.live.debounce.300ms="busqueda" placeholder="Nombre, gestión o carrera...">
            </div>

            <div class="form-group">
                <label>Carrera</label>
                <select wire:model.live="filtroCarrera">
                    <option value="">Todas las carreras</option>
                    @foreach ($carreras as $carrera)
                        <option value="{{ $carrera->id }}">
                            {{ $carrera->codigo_carrera ?? $carrera->codigo ?? '' }} - {{ $carrera->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>Estado</label>
                <select wire:model.live="estado">
                    <option value="">Todos</option>
                    <option value="activo">Activos</option>
                    <option value="inactivo">Inactivos</option>
                </select>
            </div>

            <div class="form-group">
                <label>Gestión</label>
                <input type="text" wire:model.live.debounce.300ms="gestionFiltro" placeholder="Ej: 2026-1">
            </div>

            <div class="form-group">
                <label>Paginación</label>
                <select wire:model.live="porPagina">
                    <option value="10">10 registros</option>
                    <option value="25">25 registros</option>
                    <option value="50">50 registros</option>
                    <option value="100">100 registros</option>
                </select>
            </div>
        </div>

        <div class="actions-bar">
            <div class="btn-group">
                <button wire:click="calcularGruposHabilitados" class="btn btn-secondary">
                    Calcular grupos
                </button>

                <button wire:click="generarGruposAutomaticos" class="btn btn-secondary">
                    Generar grupos automáticos
                </button>

                <button wire:click="asignarPostulantesAGrupos" class="btn btn-secondary">
                    Asignar postulantes
                </button>

                <button wire:click="limpiarFiltros" class="btn btn-danger-text" style="font-weight: 600;">
                    Limpiar filtros
                </button>
            </div>

            <div class="btn-group">
                <button wire:click="abrirCrear" class="btn">
                    + Nuevo grupo
                </button>
            </div>
        </div>
    </div>

    {{-- Listado de Datos Principal --}}
    <div class="card">
        <div style="padding-bottom: 16px; border-bottom: 1px solid #f1f5f9;">
            <h2 style="font-size: 18px;">Lista de Grupos</h2>
            <p style="margin-top: 4px;">
                Grupos registrados para la gestión seleccionada.
            </p>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th style="text-align:center; width:60px;">Nro</th>
                        <th>Grupo</th>
                        <th>Carrera</th>
                        <th>Gestión</th>
                        <th style="text-align:center;">Cupo máximo</th>
                        <th style="text-align:center;">Inscritos</th>
                        <th style="text-align:center;">Disponibles</th>
                        <th style="text-align:center;">Estado</th>
                        <th style="text-align:right; width: 240px;">Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($grupos as $index => $grupo)
                        @php
                            $disponibles = max(0, $grupo->cupos_maximo - $grupo->postulantes_count);
                        @endphp

                        <tr>
                            <td style="text-align:center; color:#94a3b8; font-weight:600;">
                                {{ $grupos->firstItem() + $index }}
                            </td>

                            <td>
                                <strong style="color: #1e293b;">{{ $grupo->nombre }}</strong>
                            </td>

                            <td>{{ $grupo->carrera->nombre ?? 'Sin carrera' }}</td>

                            <td><span style="background: #f1f5f9; padding: 4px 8px; border-radius: 6px; font-size: 13px; font-weight: 500;">{{ $grupo->gestion }}</span></td>

                            <td style="text-align:center; font-weight: 600;">{{ $grupo->cupos_maximo }}</td>

                            <td style="text-align:center; color: #2563eb; font-weight: 600;">{{ $grupo->postulantes_count }}</td>

                            <td style="text-align:center; color: #16a34a; font-weight: 600;">{{ $disponibles }}</td>

                            <td style="text-align:center;">
                                @if ($grupo->activo)
                                    <span class="badge badge-aprobado">Activo</span>
                                @else
                                    <span class="badge badge-rechazado">Inactivo</span>
                                @endif
                            </td>

                            <td style="text-align:right;">
                                <div style="display: inline-flex; gap: 6px;">
                                    <button wire:click="editar({{ $grupo->id }})" class="btn btn-secondary" style="padding: 6px 12px; font-size: 13px;">
                                        Editar
                                    </button>

                                    <button wire:click="cambiarEstado({{ $grupo->id }})" class="btn btn-secondary" style="padding: 6px 12px; font-size: 13px;">
                                        {{ $grupo->activo ? 'Inactivar' : 'Activar' }}
                                    </button>

                                    <button
                                        wire:click="eliminar({{ $grupo->id }})"
                                        wire:confirm="¿Está seguro de eliminar este grupo?"
                                        class="btn btn-danger-text" style="padding: 6px 12px; font-size: 13px; font-weight: 600;">
                                        Eliminar
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="text-align:center; padding:48px; color:#94a3b8;">
                                No existen grupos registrados con los filtros seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top:20px;">
            {{ $grupos->links() }}
        </div>
    </div>

    {{-- Ventana Modal Fluida (Create / Edit) --}}
    @if ($modal)
        <div style="position: fixed; inset: 0; background: rgba(15, 23, 42, .4); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; z-index: 50; transition: all 0.3s ease;">
            <div class="card" style="width: min(600px, 95vw); max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); border: none;">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:16px; margin-bottom:20px; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px;">
                    <div>
                        <h2 style="font-size: 22px;">
                            {{ $grupoId ? 'Editar Grupo Académico' : 'Crear Nuevo Grupo' }}
                        </h2>
                        <p style="margin-top:4px;">Complete los datos obligatorios abajo.</p>
                    </div>

                    <button wire:click="cerrarModal" class="btn btn-danger-text" style="padding: 4px 8px; font-weight: 700;">
                        ✕
                    </button>
                </div>

                <form wire:submit.prevent="guardar">
                    <div class="form-grid" style="grid-template-columns: 1fr;">
                        <div class="form-group">
                            <label>Nombre del grupo</label>
                            <input type="text" wire:model="nombre" placeholder="Ej: Grupo A - 187-4">
                            @error('nombre') <small style="color:#ef4444; font-weight: 600; margin-top: 4px;">{{ $message }}</small> @enderror
                        </div>

                        <div class="form-group">
                            <label>Carrera correspondiente</label>
                            <select wire:model="carrera_id">
                                <option value="">Seleccione...</option>
                                @foreach ($carreras as $carrera)
                                    <option value="{{ $carrera->id }}">
                                        {{ $carrera->codigo_carrera ?? $carrera->codigo ?? '' }} - {{ $carrera->nombre }}
                                    </option>
                                @endforeach
                            </select>
                            @error('carrera_id') <small style="color:#ef4444; font-weight: 600; margin-top: 4px;">{{ $message }}</small> @enderror
                        </div>

                        <div class="form-group">
                            <label>Gestión periodo</label>
                            <input type="text" wire:model="gestion" placeholder="Ej: 2026-1">
                            @error('gestion') <small style="color:#ef4444; font-weight: 600; margin-top: 4px;">{{ $message }}</small> @enderror
                        </div>

                        <div class="form-group">
                            <label>Cupo máximo permitido (Límite: 70)</label>
                            <input type="number" wire:model="cupos_maximo" min="1" max="70">
                            @error('cupos_maximo') <small style="color:#ef4444; font-weight: 600; margin-top: 4px;">{{ $message }}</small> @enderror
                        </div>

                        <div class="form-group">
                            <label>Estado del grupo</label>
                            <select wire:model="activo">
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                            @error('activo') <small style="color:#ef4444; font-weight: 600; margin-top: 4px;">{{ $message }}</small> @enderror
                        </div>

                        <div class="form-group form-full" style="margin-top: 12px; display: flex; flex-direction: row; justify-content: flex-end; gap: 10px;">
                            <span wire:loading wire:target="guardar" style="font-size: 14px; color: #64748b; align-self: center;">
                                Guardando cambios...
                            </span>
                            
                            <button type="button" wire:click="cerrarModal" class="btn btn-secondary">
                                Cancelar
                            </button>
                            
                            <button type="submit" class="btn" wire:loading.attr="disabled">
                                Guardar grupo
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>