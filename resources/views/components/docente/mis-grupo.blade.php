<?php

use App\Models\AsignacionAcademica;
use App\Models\Docente;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public $gestion = '2026-1';
    public $busqueda = '';
    public $estado = '';
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
        if (in_array($property, ['gestion', 'busqueda', 'estado', 'porPagina'])) {
            $this->resetPage();
        }
    }

    public function limpiarFiltros()
    {
        $this->gestion = '2026-1';
        $this->busqueda = '';
        $this->estado = '';
        $this->porPagina = 10;

        $this->resetPage();
    }

    public function with()
    {
        $asignaciones = collect();
        $totalGrupos = 0;
        $totalMaterias = 0;
        $totalAsignaciones = 0;

        if ($this->docente) {
            $query = AsignacionAcademica::query()
                ->with([
                    'grupo.carrera',
                    'materia',
                    'aula',
                    'horario',
                ])
                ->where('docente_id', $this->docente->id)
                ->when($this->gestion, function ($query) {
                    $query->where('gestion', $this->gestion);
                })
                ->when($this->estado, function ($query) {
                    $query->where('estado', $this->estado);
                })
                ->when($this->busqueda, function ($query) {
                    $busqueda = '%' . strtolower($this->busqueda) . '%';

                    $query->where(function ($q) use ($busqueda) {
                        $q->whereHas('grupo', function ($grupoQuery) use ($busqueda) {
                            $grupoQuery->whereRaw('LOWER(nombre) LIKE ?', [$busqueda]);
                        })
                        ->orWhereHas('materia', function ($materiaQuery) use ($busqueda) {
                            $materiaQuery->whereRaw('LOWER(nombre) LIKE ?', [$busqueda]);
                        })
                        ->orWhereHas('aula', function ($aulaQuery) use ($busqueda) {
                            $aulaQuery->whereRaw('LOWER(nombre) LIKE ?', [$busqueda]);
                        });
                    });
                });

            $totalAsignaciones = (clone $query)->count();

            $totalGrupos = (clone $query)
                ->distinct()
                ->count('grupo_id');

            $totalMaterias = (clone $query)
                ->distinct()
                ->count('materia_id');

            $asignaciones = $query
                ->orderBy('grupo_id')
                ->orderBy('materia_id')
                ->paginate($this->porPagina);
        }

        return [
            'asignaciones' => $asignaciones,
            'totalGrupos' => $totalGrupos,
            'totalMaterias' => $totalMaterias,
            'totalAsignaciones' => $totalAsignaciones,
        ];
    }
};

?>

<div class="my-groups-container">
    <style>
        /* Variables de Sistema de Diseño */
        .my-groups-container {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            color: #1e293b;
            max-width: 1280px;
            margin: 0 auto;
            padding: 24px;
            background-color: #f8fafc;
            min-height: 100vh;
        }

        /* Tarjetas con Sombras Suaves */
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

        /* Panel de Indicadores Estadísticos con Colores Temáticos */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
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
        .stat-groups::before { background-color: #3b82f6; }   /* Azul */
        .stat-subjects::before { background-color: #6366f1; } /* Índigo */
        .stat-active::before { background-color: #10b981; }   /* Esmeralda */

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
        .stat-groups strong { color: #2563eb; }
        .stat-subjects strong { color: #4f46e5; }
        .stat-active strong { color: #059669; }

        /* Rejilla de Filtros y Campos de Formulario */
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

        /* Tablas de Información Académica */
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

        /* Subtextos en celdas */
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
        }
        .badge-aprobado { background-color: #dcfce7; color: #15803d; }   /* Activa */
        .badge-pendiente { background-color: #eff6ff; color: #1e40af; }  /* Finalizada */
        .badge-rechazado { background-color: #fee2e2; color: #b91c1c; }  /* Inactiva */

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
                <h2 style="font-size: 24px;">Mis Grupos</h2>
                <p>
                    Consulte los grupos, materias, aulas y horarios asignados para la gestión académica.
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

    {{-- Bloques de Estadísticas --}}
    <div class="grid">
        <div class="stat stat-groups">
            <span>Grupos asignados</span>
            <strong>{{ $totalGrupos }}</strong>
        </div>

        <div class="stat stat-subjects">
            <span>Materias asignadas</span>
            <strong>{{ $totalMaterias }}</strong>
        </div>

        <div class="stat stat-active">
            <span>Asignaciones activas</span>
            <strong>{{ $totalAsignaciones }}</strong>
        </div>
    </div>

    {{-- Filtros y Parámetros de Búsqueda --}}
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
                <label>Buscar</label>
                <input 
                    type="text" 
                    wire:model.live.debounce.300ms="busqueda" 
                    placeholder="Grupo, materia o aula..."
                >
            </div>

            <div class="form-group">
                <label>Estado</label>
                <select wire:model.live="estado">
                    <option value="">Todos</option>
                    <option value="activa">Activa</option>
                    <option value="inactiva">Inactiva</option>
                    <option value="finalizada">Finalizada</option>
                </select>
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

    {{-- Tabla Principal de Carga Académica --}}
    <div class="card">
        <div style="padding-bottom: 16px; border-bottom: 1px solid #f1f5f9; margin-bottom: 8px;">
            <h2 style="font-size: 18px;">Detalle de Asignaciones</h2>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th style="text-align:center; width:60px;">Nro</th>
                        <th>Grupo</th>
                        <th>Carrera</th>
                        <th>Materia</th>
                        <th>Aula / Ubicación</th>
                        <th>Horario Semanal</th>
                        <th>Gestión</th>
                        <th style="text-align:center; width: 130px;">Estado</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($asignaciones as $index => $asignacion)
                        <tr>
                            <td style="text-align:center; color:#94a3b8; font-weight:600;">
                                {{ $asignaciones->firstItem() + $index }}
                            </td>

                            <td>
                                <strong style="color: #0f172a; font-size: 14.5px;">{{ $asignacion->grupo->nombre ?? 'Sin grupo' }}</strong>
                            </td>

                            <td>
                                <span style="font-weight: 500; color: #475569;">{{ $asignacion->grupo->carrera->nombre ?? 'Sin carrera' }}</span>
                            </td>

                            <td>
                                <strong style="color: #1e40af; background: #eff6ff; padding: 4px 8px; border-radius: 6px;">
                                    {{ $asignacion->materia->nombre ?? 'Sin materia' }}
                                </strong>
                            </td>

                            <td>
                                <strong style="color: #334155;">{{ $asignacion->aula->nombre ?? 'Sin aula' }}</strong>
                                <span class="subtext">{{ $asignacion->aula->ubicacion ?? '' }}</span>
                            </td>

                            <td>
                                <strong style="color: #0f172a;">{{ ucfirst($asignacion->horario->dia ?? '') }}</strong>
                                <span class="subtext" style="font-weight: 500; color: #16a34a;">
                                    {{ ucfirst($asignacion->horario->turno ?? '') }} | {{ $asignacion->horario->hora_inicio ?? '' }} - {{ $asignacion->horario->hora_final ?? '' }}
                                </span>
                            </td>

                            <td>
                                <span style="background: #f1f5f9; padding: 4px 8px; border-radius: 6px; font-size: 13px; font-weight: 600; color: #475569;">
                                    {{ $asignacion->gestion }}
                                </span>
                            </td>

                            <td style="text-align:center;">
                                @php
                                    $estadoActual = $asignacion->estado ?? 'activa';

                                    $badgeEstado = match ($estadoActual) {
                                        'activa' => 'badge-aprobado',
                                        'finalizada' => 'badge-pendiente',
                                        default => 'badge-rechazado',
                                    };
                                @endphp

                                <span class="badge {{ $badgeEstado }}">
                                    {{ ucfirst($estadoActual) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align:center; padding:48px; color:#94a3b8; font-weight: 500;">
                                No existen grupos ni asignaciones académicas registradas para su cuenta en este periodo.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top:20px;">
            {{ $asignaciones->links() }}
        </div>
    </div>
</div>