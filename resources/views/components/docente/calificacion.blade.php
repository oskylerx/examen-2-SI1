<?php

use App\Models\AsignacionAcademica;
use App\Models\Docente;
use App\Models\Examen;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public $gestion = '2026-1';
    public $grupo_id = '';
    public $materia_id = '';
    public $estado = '';
    public $busqueda = '';
    public $porPagina = 10;

    public $docente = null;

    public function mount()
    {
        $this->docente = Docente::where('user_id', auth()->id())->first();

        if (! $this->docente) {
            session()->flash('mensaje', 'No se encontró el docente vinculado al usuario actual.');
        }
    }

    public function updated($property)
    {
        if (in_array($property, [
            'gestion',
            'grupo_id',
            'materia_id',
            'estado',
            'busqueda',
            'porPagina',
        ])) {
            $this->resetPage();
        }
    }

    public function updatedGrupoId()
    {
        $this->materia_id = '';
        $this->resetPage();
    }

    public function limpiarFiltros()
    {
        $this->gestion = '2026-1';
        $this->grupo_id = '';
        $this->materia_id = '';
        $this->estado = '';
        $this->busqueda = '';
        $this->porPagina = 10;

        $this->resetPage();
    }

    private function asignacionesDocente()
    {
        if (! $this->docente) {
            return collect();
        }

        return AsignacionAcademica::with(['grupo.carrera', 'materia'])
            ->where('docente_id', $this->docente->id)
            ->where('gestion', $this->gestion)
            ->where('estado', 'activa')
            ->get();
    }

    private function queryExamenes()
    {
        $asignaciones = $this->asignacionesDocente();

        $grupoIds = $asignaciones
            ->pluck('grupo_id')
            ->unique()
            ->values()
            ->toArray();

        $materiaIds = $asignaciones
            ->pluck('materia_id')
            ->unique()
            ->values()
            ->toArray();

        return Examen::query()
            ->with([
                'postulante.user',
                'postulante.grupo.carrera',
                'materia',
                'calificaciones',
            ])
            ->whereIn('materia_id', $materiaIds)
            ->whereHas('postulante', function ($query) use ($grupoIds) {
                $query->whereIn('grupo_id', $grupoIds);
            })
            ->when($this->grupo_id, function ($query) {
                $query->whereHas('postulante', function ($postulanteQuery) {
                    $postulanteQuery->where('grupo_id', $this->grupo_id);
                });
            })
            ->when($this->materia_id, function ($query) {
                $query->where('materia_id', $this->materia_id);
            })
            ->when($this->estado, function ($query) {
                $query->where('estado', $this->estado);
            })
            ->when($this->busqueda, function ($query) {
                $busqueda = '%' . strtolower($this->busqueda) . '%';

                $query->whereHas('postulante.user', function ($userQuery) use ($busqueda) {
                    $userQuery->whereRaw('LOWER(name) LIKE ?', [$busqueda])
                        ->orWhereRaw('LOWER(apellido) LIKE ?', [$busqueda])
                        ->orWhereRaw('LOWER(ci) LIKE ?', [$busqueda])
                        ->orWhereRaw('LOWER(username) LIKE ?', [$busqueda]);
                });
            });
    }

    private function obtenerNota($examen, $tipo)
    {
        return optional($examen->calificaciones->firstWhere('tipo', $tipo))->nota;
    }

    public function with()
    {
        $asignaciones = $this->asignacionesDocente();

        $grupos = $asignaciones
            ->pluck('grupo')
            ->filter()
            ->unique('id')
            ->sortBy('nombre')
            ->values();

        if ($this->grupo_id) {
            $materias = $asignaciones
                ->where('grupo_id', (int) $this->grupo_id)
                ->pluck('materia')
                ->filter()
                ->unique('id')
                ->sortBy('nombre')
                ->values();
        } else {
            $materias = $asignaciones
                ->pluck('materia')
                ->filter()
                ->unique('id')
                ->sortBy('nombre')
                ->values();
        }

        $query = $this->queryExamenes();

        $totalCalificaciones = (clone $query)->count();

        $totalAprobados = (clone $query)
            ->where('estado', 'aprobado')
            ->count();

        $totalReprobados = (clone $query)
            ->where('estado', 'reprobado')
            ->count();

        $promedioGeneral = (clone $query)->avg('promedio_final');

        $examenes = $query
            ->orderByDesc('fecha_registro')
            ->paginate($this->porPagina);

        return [
            'grupos' => $grupos,
            'materias' => $materias,
            'examenes' => $examenes,
            'totalCalificaciones' => $totalCalificaciones,
            'totalAprobados' => $totalAprobados,
            'totalReprobados' => $totalReprobados,
            'promedioGeneral' => $promedioGeneral ? round($promedioGeneral, 2) : 0,
        ];
    }
};

?>

<div class="grades-report-container">
    <style>
        /* Variables de Sistema de Diseño */
        .grades-report-container {
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

        /* Tipografías */
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

        /* Panel de Indicadores con Bordes Laterales Temáticos */
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
            position: relative;
            overflow: hidden;
        }
        .stat::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
        }
        .stat-total::before { background-color: #3b82f6; }    /* Azul */
        .stat-approved::before { background-color: #10b981; } /* Verde */
        .stat-failed::before { background-color: #ef4444; }   /* Rojo */
        .stat-average::before { background-color: #8b5cf6; }  /* Morado */

        .stat span {
            color: #64748b;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 6px;
        }
        .stat strong {
            font-size: 32px;
            font-weight: 800;
            letter-spacing: -0.05em;
        }
        .stat-total strong { color: #2563eb; }
        .stat-approved strong { color: #059669; }
        .stat-failed strong { color: #dc2626; }
        .stat-average strong { color: #7c3aed; }

        /* Rejilla de Filtros */
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

        /* Barras de Acciones */
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

        /* Botón de Estilo Moderno */
        .btn-danger-text {
            background: transparent;
            color: #ef4444;
            padding: 10px 14px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.2s ease;
        }
        .btn-danger-text:hover {
            background: #fef2f2;
        }

        /* Tablas de Reporte Académico */
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
            vertical-align: middle;
        }
        .table tr:last-child td {
            border-bottom: none;
        }
        .table tr:hover td {
            background-color: #f8fafc;
        }

        /* Subtextos descriptivos en celdas */
        .subtext {
            color: #64748b;
            font-size: 12px;
            display: block;
            margin-top: 2px;
        }

        /* Badges de Estado Fluido */
        .badge {
            padding: 5px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
            text-align: center;
            text-transform: capitalize;
        }
        .badge-aprobado { background-color: #dcfce7; color: #15803d; }
        .badge-rechazado { background-color: #fee2e2; color: #b91c1c; }
        .badge-pendiente { background-color: #f1f5f9; color: #475569; }

        /* Elementos de carga y Alertas */
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

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: .6; }
        }
    </style>

    {{-- Cabecera del Módulo --}}
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div>
                <h2 style="font-size: 24px;">Calificaciones</h2>
                <p>
                    Consulte las notas registradas por grupo y materia asignada.
                </p>
            </div>

            <div wire:loading class="loading-badge">
                Actualizando datos...
            </div>
        </div>
    </div>

    {{-- Notificaciones Flash --}}
    @if (session()->has('mensaje'))
        <div class="alert-message">
            {{ session('mensaje') }}
        </div>
    @endif

    {{-- Tarjetas de Control Estadístico --}}
    <div class="grid">
        <div class="stat stat-total">
            <span>Calificaciones registradas</span>
            <strong>{{ $totalCalificaciones }}</strong>
        </div>

        <div class="stat stat-approved">
            <span>Aprobados</span>
            <strong>{{ $totalAprobados }}</strong>
        </div>

        <div class="stat stat-failed">
            <span>Reprobados</span>
            <strong>{{ $totalReprobados }}</strong>
        </div>

        <div class="stat stat-average">
            <span>Promedio general</span>
            <strong>{{ number_format($promedioGeneral, 2) }}</strong>
        </div>
    </div>

    {{-- Panel de Filtros Avanzados --}}
    <div class="card">
        <h2>Filtros</h2>

        <div class="form-grid">
            <div class="form-group">
                <label>Gestión</label>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="gestion"
                    placeholder="Ej: 2026-1"
                >
            </div>

            <div class="form-group">
                <label>Grupo</label>
                <select wire:model.live="grupo_id">
                    <option value="">Todos los grupos</option>
                    @foreach ($grupos as $grupo)
                        <option value="{{ $grupo->id }}">
                            {{ $grupo->nombre }} @if ($grupo->carrera) - {{ $grupo->carrera->nombre }} @endif
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>Materia</label>
                <select wire:model.live="materia_id">
                    <option value="">Todas las materias</option>
                    @foreach ($materias as $materia)
                        <option value="{{ $materia->id }}">{{ $materia->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>Estado</label>
                <select wire:model.live="estado">
                    <option value="">Todos</option>
                    <option value="aprobado">Aprobados</option>
                    <option value="reprobado">Reprobados</option>
                </select>
            </div>

            <div class="form-group">
                <label>Buscar postulante</label>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="busqueda"
                    placeholder="Nombre, apellido, CI o usuario..."
                >
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
                <button wire:click="limpiarFiltros" class="btn btn-danger-text" style="font-weight: 600;">
                    Limpiar filtros
                </button>
            </div>
        </div>
    </div>

    {{-- Tabla Principal de Calificaciones Recopiladas --}}
    <div class="card">
        <div style="padding-bottom: 16px; border-bottom: 1px solid #f1f5f9; margin-bottom: 8px;">
            <h2 style="font-size: 18px;">Detalle de Calificaciones</h2>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th style="text-align:center; width:60px;">Nro</th>
                        <th>Postulante</th>
                        <th>CI</th>
                        <th>Grupo</th>
                        <th>Materia</th>
                        <th style="text-align:center; width:80px;">P1</th>
                        <th style="text-align:center; width:80px;">P2</th>
                        <th style="text-align:center; width:80px;">EF</th>
                        <th style="text-align:center; width:100px;">Promedio</th>
                        <th style="text-align:center; width:130px;">Estado</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($examenes as $index => $examen)
                        @php
                            $p1 = $this->obtenerNota($examen, 'p1');
                            $p2 = $this->obtenerNota($examen, 'p2');
                            $ef = $this->obtenerNota($examen, 'ef');
                        @endphp

                        <tr>
                            <td style="text-align:center; color:#94a3b8; font-weight:600;">
                                {{ $examenes->firstItem() + $index }}
                            </td>

                            <td>
                                <strong style="color: #0f172a; font-size: 14.5px;">
                                    {{ $examen->postulante->user->name ?? '' }} {{ $examen->postulante->user->apellido ?? '' }}
                                </strong>
                                <span class="subtext">{{ $examen->postulante->user->username ?? '' }}</span>
                            </td>

                            <td>
                                <span style="font-weight: 500; color: #475569;">{{ $examen->postulante->user->ci ?? 'Sin CI' }}</span>
                            </td>

                            <td>
                                <strong style="color: #334155;">{{ $examen->postulante->grupo->nombre ?? 'Sin grupo' }}</strong>
                            </td>

                            <td>
                                <strong style="color: #475569;">{{ $examen->materia->nombre ?? 'Sin materia' }}</strong>
                            </td>

                            <td style="text-align:center; font-weight: 500; color: #334155;">
                                {{ $p1 ?? '-' }}
                            </td>

                            <td style="text-align:center; font-weight: 500; color: #334155;">
                                {{ $p2 ?? '-' }}
                            </td>

                            <td style="text-align:center; font-weight: 500; color: #334155;">
                                {{ $ef ?? '-' }}
                            </td>

                            <td style="text-align:center;">
                                <span style="font-size: 15px; font-weight: 700; color: {{ $examen->estado === 'aprobado' ? '#16a34a' : '#dc2626' }};">
                                    {{ number_format($examen->promedio_final, 2) }}
                                </span>
                            </td>

                            <td style="text-align:center;">
                                @php
                                    $badgeEstado = match ($examen->estado) {
                                        'aprobado' => 'badge-aprobado',
                                        'reprobado' => 'badge-rechazado',
                                        default => 'badge-pendiente',
                                    };
                                @endphp
                                <span class="badge {{ $badgeEstado }}">
                                    {{ $examen->estado }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" style="text-align:center; padding:48px; color:#94a3b8; font-weight: 500;">
                                No existen calificaciones registradas para los filtros seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top:20px;">
            {{ $examenes->links() }}
        </div>
    </div>
</div>