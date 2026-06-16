<?php

use App\Models\AsignacionAcademica;
use App\Models\Docente;
use App\Models\Examen;
use App\Models\Postulante;
use Livewire\Component;

new class extends Component
{
    public $docente = null;
    public string $gestion = '2026-1';

    public function mount()
    {
        $this->docente = Docente::with('user')
            ->where('user_id', auth()->id())
            ->first();
    }

    public function updatedGestion()
    {
        //
    }

    private function asignacionesDocente()
    {
        if (! $this->docente) {
            return collect();
        }

        return AsignacionAcademica::with([
                'grupo.carrera',
                'materia',
                'aula',
                'horario',
            ])
            ->where('docente_id', $this->docente->id)
            ->where('gestion', $this->gestion)
            ->where('estado', 'activa')
            ->orderBy('grupo_id')
            ->orderBy('materia_id')
            ->get();
    }

    private function examenesDelDocente($asignaciones)
    {
        if ($asignaciones->isEmpty()) {
            return collect();
        }

        return Examen::with([
                'postulante.user',
                'postulante.grupo',
                'materia',
                'calificaciones',
            ])
            ->where(function ($query) use ($asignaciones) {
                foreach ($asignaciones as $asignacion) {
                    $query->orWhere(function ($q) use ($asignacion) {
                        $q->where('materia_id', $asignacion->materia_id)
                            ->whereHas('postulante', function ($postulanteQuery) use ($asignacion) {
                                $postulanteQuery->where('grupo_id', $asignacion->grupo_id);
                            });
                    });
                }
            })
            ->get();
    }

    private function calcularEvaluacionesEsperadas($asignaciones)
    {
        $total = 0;

        foreach ($asignaciones as $asignacion) {
            $total += Postulante::where('grupo_id', $asignacion->grupo_id)
                ->where('estado_inscripcion', 'aceptado')
                ->count();
        }

        return $total;
    }

    private function resumenPorMateria($examenes)
    {
        return $examenes
            ->groupBy('materia_id')
            ->map(function ($items) {
                $materia = $items->first()->materia;

                $total = $items->count();
                $aprobados = $items->where('estado', 'aprobado')->count();
                $reprobados = $items->where('estado', 'reprobado')->count();

                return [
                    'materia' => $materia->nombre ?? 'Sin materia',
                    'evaluados' => $total,
                    'aprobados' => $aprobados,
                    'reprobados' => $reprobados,
                    'promedio' => $total > 0 ? round($items->avg('promedio_final'), 2) : 0,
                    'porcentaje_aprobacion' => $total > 0 ? round(($aprobados / $total) * 100, 2) : 0,
                ];
            })
            ->sortBy('materia')
            ->values();
    }

    private function resumenPorGrupo($examenes)
    {
        return $examenes
            ->filter(fn ($examen) => $examen->postulante && $examen->postulante->grupo)
            ->groupBy(fn ($examen) => $examen->postulante->grupo_id)
            ->map(function ($items) {
                $grupo = $items->first()->postulante->grupo;

                $total = $items->count();
                $aprobados = $items->where('estado', 'aprobado')->count();
                $reprobados = $items->where('estado', 'reprobado')->count();

                return [
                    'grupo' => $grupo->nombre ?? 'Sin grupo',
                    'evaluaciones' => $total,
                    'aprobados' => $aprobados,
                    'reprobados' => $reprobados,
                    'promedio' => $total > 0 ? round($items->avg('promedio_final'), 2) : 0,
                    'porcentaje_aprobacion' => $total > 0 ? round(($aprobados / $total) * 100, 2) : 0,
                ];
            })
            ->sortBy('grupo')
            ->values();
    }

    public function with()
    {
        $asignaciones = $this->asignacionesDocente();

        $examenes = $this->examenesDelDocente($asignaciones);

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

        $totalGrupos = count($grupoIds);
        $totalMaterias = count($materiaIds);
        $totalAsignaciones = $asignaciones->count();

        $totalPostulantes = count($grupoIds) > 0
            ? Postulante::whereIn('grupo_id', $grupoIds)
                ->where('estado_inscripcion', 'aceptado')
                ->count()
            : 0;

        $totalEvaluaciones = $examenes->count();

        $evaluacionesEsperadas = $this->calcularEvaluacionesEsperadas($asignaciones);

        $evaluacionesPendientes = max(0, $evaluacionesEsperadas - $totalEvaluaciones);

        $aprobados = $examenes->where('estado', 'aprobado')->count();
        $reprobados = $examenes->where('estado', 'reprobado')->count();

        $promedioGeneral = $totalEvaluaciones > 0
            ? round($examenes->avg('promedio_final'), 2)
            : 0;

        $porcentajeAprobacion = $totalEvaluaciones > 0
            ? round(($aprobados / $totalEvaluaciones) * 100, 2)
            : 0;

        $ultimosResultados = $examenes
            ->sortByDesc('fecha_registro')
            ->take(8)
            ->values();

        return [
            'asignaciones' => $asignaciones,
            'totalGrupos' => $totalGrupos,
            'totalMaterias' => $totalMaterias,
            'totalAsignaciones' => $totalAsignaciones,
            'totalPostulantes' => $totalPostulantes,
            'totalEvaluaciones' => $totalEvaluaciones,
            'evaluacionesEsperadas' => $evaluacionesEsperadas,
            'evaluacionesPendientes' => $evaluacionesPendientes,
            'aprobados' => $aprobados,
            'reprobados' => $reprobados,
            'promedioGeneral' => $promedioGeneral,
            'porcentajeAprobacion' => $porcentajeAprobacion,
            'porMateria' => $this->resumenPorMateria($examenes),
            'porGrupo' => $this->resumenPorGrupo($examenes),
            'ultimosResultados' => $ultimosResultados,
        ];
    }
};

?>

<div class="teacher-dashboard-container">
    <style>
        /* Variables y Estilos del Sistema de Diseño Premium */
        .teacher-dashboard-container {
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
            font-size: 13px;
            font-weight: 700;
            margin: 0 0 16px 0;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #475569;
        }
        p {
            color: #64748b;
            font-size: 14px;
            margin: 0;
            line-height: 1.5;
        }

        /* Rejillas Adaptativas (Layout Grids) */
        .grid-kpi {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .grid-2 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
            gap: 24px;
            margin-bottom: 24px;
        }

        /* Panel de Filtros y Configuración */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
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
        .form-group input {
            padding: 10px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
            color: #334155;
            background-color: #ffffff;
            transition: all 0.2s ease;
        }
        .form-group input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.12);
        }

        /* Indicadores Estadísticos KPI (10 variantes) */
        .stat {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 18px;
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
            background-color: #cbd5e1;
        }
        .stat-groups::before { background-color: #3b82f6; }       /* Azul */
        .stat-subjects::before { background-color: #6366f1; }     /* Índigo */
        .stat-active::before { background-color: #0ea5e9; }       /* Cielo */
        .stat-applicants::before { background-color: #475569; }   /* Pizarra */
        .stat-evals::before { background-color: #0f172a; }        /* Oscuro */
        .stat-pending::before { background-color: #f59e0b; }      /* Ámbar */
        .stat-approved::before { background-color: #10b981; }     /* Esmeralda */
        .stat-failed::before { background-color: #ef4444; }       /* Rojo */
        .stat-average::before { background-color: #8b5cf6; }      /* Morado */
        .stat-percent::before { background-color: #ec4899; }      /* Rosa */

        .stat span {
            color: #64748b;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 6px;
        }
        .stat strong {
            font-size: 26px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.04em;
        }
        .stat-groups strong { color: #2563eb; }
        .stat-subjects strong { color: #4f46e5; }
        .stat-active strong { color: #0284c7; }
        .stat-pending strong { color: #d97706; }
        .stat-approved strong { color: #059669; }
        .stat-failed strong { color: #dc2626; }
        .stat-average strong { color: #7c3aed; }
        .stat-percent strong { color: #db2777; }

        /* Enlaces de Accesos Rápidos */
        .btn-group-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 12px;
            width: 100%;
        }
        .btn-quick-link {
            padding: 12px 16px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 8px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #334155;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.2s ease;
        }
        .btn-quick-link:hover {
            background: #eff6ff;
            border-color: #bfdbfe;
            color: #1d4ed8;
            transform: translateY(-1px);
        }

        /* Estilos de Tablas */
        .table-responsive {
            overflow-x: auto;
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
            padding: 12px 16px;
            border-bottom: 2px solid #e2e8f0;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.05em;
        }
        .table td {
            padding: 12px 16px;
            border-bottom: 1px solid #edf2f7;
            color: #334155;
            vertical-align: middle;
        }
        .table tr:last-child td {
            border-bottom: none;
        }
        .table-vertical th {
            background: #f8fafc;
            color: #475569;
            font-weight: 600;
            text-align: left;
            width: 25%;
            font-size: 13px;
            border-bottom: 1px solid #f1f5f9;
        }
        .table-vertical td {
            color: #0f172a;
            font-weight: 500;
            border-bottom: 1px solid #f1f5f9;
        }
        .table-vertical tr:last-child th, .table-vertical tr:last-child td {
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

        /* Badges de Estado */
        .badge {
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        .badge-aprobado { background-color: #dcfce7; color: #15803d; }
        .badge-pendiente { background-color: #e0f2fe; color: #0369a1; }
        .badge-rechazado { background-color: #fee2e2; color: #b91c1c; }

        /* Elemento de carga */
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
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: .6; } }
    </style>

    {{-- Cabecera del Dashboard --}}
    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
            <div>
                <h1 style="font-size: 24px; font-weight:800; color:#0f172a; margin:0;">Dashboard Docente</h1>
                <p style="margin-top:4px;">
                    Panel académico del docente: grupos asignados, materias, calificaciones registradas y resultados de sus postulantes.
                </p>
            </div>

            <div wire:loading class="loading-badge">
                Actualizando datos...
            </div>
        </div>
    </div>

    {{-- Control de Excepciones: Docente No Encontrado --}}
    @if (! $docente)
        <div class="card" style="text-align: center; padding: 40px 20px; border-color: #fee2e2; background: #fff5f5;">
            <span style="font-size: 42px; display: block; margin-bottom: 12px;">⚠️</span>
            <h2 style="color: #b91c1c; font-size: 18px; margin-bottom: 6px;">Docente no encontrado</h2>
            <p style="color: #ef4444; font-weight: 500;">
                No se encontró un registro de docente vinculado al usuario actual. Por favor, contacte con el administrador.
            </p>
        </div>
    @else

        {{-- Perfil del Docente y Filtro de Gestión en Paralelo --}}
        <div class="grid-2">
            <div class="card" style="margin-bottom: 0;">
                <h2>Datos del docente</h2>
                <div class="table-responsive" style="border: none;">
                    <table class="table table-vertical">
                        <tbody>
                            <tr>
                                <th>Nombre completo</th>
                                <td>{{ $docente->user->name ?? '' }} {{ $docente->user->apellido ?? '' }}</td>
                            </tr>
                            <tr>
                                <th>Usuario</th>
                                <td style="color: #2563eb; font-family: monospace;">{{ $docente->user->username ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>CI</th>
                                <td>{{ $docente->user->ci ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Correo</th>
                                <td style="font-weight: 400; color: #475569;">{{ $docente->user->email ?? '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card" style="margin-bottom: 0; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <h2>Gestión académica</h2>
                    <p style="margin-bottom: 20px;">Filtre la carga horaria y las estadísticas de rendimiento según el periodo correspondiente.</p>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Gestión / Periodo</label>
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="gestion"
                            placeholder="Ej: 2026-1"
                        >
                    </div>
                </div>
            </div>
        </div>

        {{-- Panel General de los 10 Bloques KPI Estilizados --}}
        <div class="grid-kpi" style="margin-top: 24px;">
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

            <div class="stat stat-applicants">
                <span>Postulantes en grupos</span>
                <strong>{{ $totalPostulantes }}</strong>
            </div>

            <div class="stat stat-evals">
                <span>Evaluaciones registradas</span>
                <strong>{{ $totalEvaluaciones }}</strong>
            </div>

            <div class="stat stat-pending">
                <span>Evaluaciones pendientes</span>
                <strong>{{ $evaluacionesPendientes }}</strong>
            </div>

            <div class="stat stat-approved">
                <span>Aprobados</span>
                <strong>{{ $aprobados }}</strong>
            </div>

            <div class="stat stat-failed">
                <span>Reprobados</span>
                <strong>{{ $reprobados }}</strong>
            </div>

            <div class="stat stat-average">
                <span>Promedio general</span>
                <strong>{{ number_format($promedioGeneral, 2) }}</strong>
            </div>

            <div class="stat stat-percent">
                <span>% Aprobación</span>
                <strong>{{ $porcentajeAprobacion }}%</strong>
            </div>
        </div>

        {{-- Accesos Rápidos Modulares --}}
        <div class="card">
            <h2>Accesos rápidos</h2>
            <div class="btn-group-links">
                @if (Route::has('docente.mis-grupos'))
                    <a href="{{ route('docente.mis-grupos') }}" class="btn-quick-link">
                        <span>Mis grupos académicos</span> ➜
                    </a>
                @endif

                @if (Route::has('docente.notas'))
                    <a href="{{ route('docente.notas') }}" class="btn-quick-link">
                        <span>Registrar notas y parciales</span> ➜
                    </a>
                @endif

                @if (Route::has('docente.calificaciones'))
                    <a href="{{ route('docente.calificaciones') }}" class="btn-quick-link">
                        <span>Consulta de calificaciones</span> ➜
                    </a>
                @endif

                @if (Route::has('docente.resultados'))
                    <a href="{{ route('docente.resultados') }}" class="btn-quick-link">
                        <span>Resultados finales de admisión</span> ➜
                    </a>
                @endif
            </div>
        </div>

        {{-- Tabla de Carga Horaria y Asignaciones Oficiales --}}
        <div class="card">
            <div style="padding-bottom: 12px; border-bottom: 1px solid #f1f5f9; margin-bottom: 16px;">
                <h2 style="margin:0;">Mis asignaciones académicas</h2>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Grupo</th>
                            <th>Carrera</th>
                            <th>Materia</th>
                            <th>Aula física</th>
                            <th>Horario asignado</th>
                            <th style="text-align:center; width: 120px;">Estado</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($asignaciones as $asignacion)
                            <tr>
                                <td>
                                    <strong style="color: #0f172a; font-size:14.5px;">{{ $asignacion->grupo->nombre ?? 'Sin grupo' }}</strong>
                                </td>

                                <td>
                                    <span style="font-weight: 500; color:#475569;">{{ $asignacion->grupo->carrera->nombre ?? 'Sin carrera' }}</span>
                                </td>

                                <td>
                                    <strong style="color: #1e40af; background: #eff6ff; padding: 4px 8px; border-radius: 6px; font-size:13px;">
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

                                <td style="text-align:center;">
                                    @if ($asignacion->estado === 'activa')
                                        <span class="badge badge-aprobado">Activa</span>
                                    @else
                                        <span class="badge badge-pendiente">{{ ucfirst($asignacion->estado) }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="text-align:center; padding:32px; color:#94a3b8; font-weight: 500;">
                                    No tiene asignaciones académicas activas registradas en esta gestión.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Reportes de Resumen Cruzados en Dos Columnas --}}
        <div class="grid-2">
            
            <!-- Resumen por Materia -->
            <div class="card" style="margin-bottom:0;">
                <div style="padding-bottom: 12px; border-bottom: 1px solid #f1f5f9; margin-bottom: 16px;">
                    <h2>Resumen estadístico por materia</h2>
                </div>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Materia</th>
                                <th style="text-align:center;">Eval.</th>
                                <th style="text-align:center;">Aprob.</th>
                                <th style="text-align:center;">Reprob.</th>
                                <th style="text-align:center;">Promedio</th>
                                <th style="text-align:center;">% Aprob.</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($porMateria as $fila)
                                <tr>
                                    <td><strong style="color:#1e293b;">{{ $fila['materia'] }}</strong></td>
                                    <td style="text-align:center; font-weight: 500;">{{ $fila['evaluados'] }}</td>
                                    <td style="text-align:center; color:#16a34a; font-weight:600;">{{ $fila['aprobados'] }}</td>
                                    <td style="text-align:center; color:#dc2626; font-weight:600;">{{ $fila['reprobados'] }}</td>
                                    <td style="text-align:center;"><strong>{{ number_format($fila['promedio'], 2) }}</strong></td>
                                    <td style="text-align:center; font-weight:700; color: #4f46e5;">{{ $fila['porcentaje_aprobacion'] }}%</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" style="text-align:center; padding:32px; color:#94a3b8; font-weight: 500;">
                                        No existen calificaciones registradas por materia.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Resumen por Grupo -->
            <div class="card" style="margin-bottom:0;">
                <div style="padding-bottom: 12px; border-bottom: 1px solid #f1f5f9; margin-bottom: 16px;">
                    <h2>Resumen estadístico por grupo</h2>
                </div>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Grupo</th>
                                <th style="text-align:center;">Evaluaciones</th>
                                <th style="text-align:center;">Aprobados</th>
                                <th style="text-align:center;">Reprobados</th>
                                <th style="text-align:center;">Promedio</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($porGrupo as $fila)
                                <tr>
                                    <td><strong style="color:#0f172a;">{{ $fila['grupo'] }}</strong></td>
                                    <td style="text-align:center; font-weight: 500;">{{ $fila['evaluaciones'] }}</td>
                                    <td style="text-align:center; color:#16a34a;">{{ $fila['aprobados'] }}</td>
                                    <td style="text-align:center; color:#dc2626;">{{ $fila['reprobados'] }}</td>
                                    <td style="text-align:center; color:#7c3aed;"><strong>{{ number_format($fila['promedio'], 2) }}</strong></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" style="text-align:center; padding:32px; color:#94a3b8; font-weight: 500;">
                                        No existen calificaciones registradas por grupo.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Bitácora de Últimas Calificaciones Publicadas --}}
        <div class="card" style="margin-bottom: 0; margin-top: 24px;">
            <div style="padding-bottom: 12px; border-bottom: 1px solid #f1f5f9; margin-bottom: 16px;">
                <h2>Últimos resultados registrados</h2>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Postulante</th>
                            <th>Grupo Académico</th>
                            <th>Materia evaluada</th>
                            <th style="text-align:center; width: 120px;">Promedio final</th>
                            <th style="text-align:center; width: 140px;">Estado</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($ultimosResultados as $resultado)
                            <tr>
                                <td>
                                    <strong style="color: #0f172a; font-size:14.5px;">
                                        {{ $resultado->postulante->user->name ?? '' }} {{ $resultado->postulante->user->apellido ?? '' }}
                                    </strong>
                                    <span class="subtext">{{ $resultado->postulante->user->username ?? '' }}</span>
                                </td>

                                <td>
                                    <span style="font-weight: 600; color:#334155;">{{ $resultado->postulante->grupo->nombre ?? 'Sin grupo' }}</span>
                                </td>

                                <td>
                                    <span style="font-weight: 500; color:#475569;">{{ $resultado->materia->nombre ?? 'Sin materia' }}</span>
                                </td>

                                <td style="text-align:center;">
                                    <strong style="font-size: 15px; color: #0f172a;">{{ number_format($resultado->promedio_final, 2) }}</strong>
                                </td>

                                <td style="text-align:center;">
                                    @if ($resultado->estado === 'aprobado')
                                        <span class="badge badge-aprobado">Aprobado</span>
                                    @elseif ($resultado->estado === 'reprobado')
                                        <span class="badge badge-rechazado">Reprobado</span>
                                    @else
                                        <span class="badge badge-pendiente">{{ ucfirst($resultado->estado) }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="text-align:center; padding:32px; color:#94a3b8; font-weight: 500;">
                                    Todavía no existen calificaciones o resultados procesados en su planilla.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    @endif
</div>