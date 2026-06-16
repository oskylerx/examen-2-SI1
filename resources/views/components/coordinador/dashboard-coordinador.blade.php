<?php

use App\Models\AsignacionAcademica;
use App\Models\AsignacionCupo;
use App\Models\Coordinador;
use App\Models\Docente;
use App\Models\DocumentoPostulante;
use App\Models\Examen;
use App\Models\Grupo;
use App\Models\Materia;
use App\Models\Postulante;
use Livewire\Component;

new class extends Component
{
    public $coordinador = null;

    public function mount()
    {
        $this->coordinador = Coordinador::where('user_id', auth()->id())->first();
    }

    private function gruposBase()
    {
        /*
         * Si tus grupos tienen coordinador_id asignado, se muestran solo los del coordinador.
         * Si todavía no estás asignando coordinador_id en grupo, se muestran todos los grupos activos.
         */
        $query = Grupo::query()->where('activo', true);

        if ($this->coordinador) {
            $gruposAsignados = Grupo::where('coordinador_id', $this->coordinador->id)->count();

            if ($gruposAsignados > 0) {
                $query->where('coordinador_id', $this->coordinador->id);
            }
        }

        return $query;
    }

    public function with()
    {
        $grupoIds = $this->gruposBase()
            ->pluck('id')
            ->toArray();

        $totalGrupos = count($grupoIds);

        $totalPostulantes = Postulante::whereIn('grupo_id', $grupoIds)->count();

        $postulantesAceptados = Postulante::whereIn('grupo_id', $grupoIds)
            ->where('estado_inscripcion', 'aceptado')
            ->count();

        $postulantesPendientes = Postulante::where('estado_inscripcion', 'pendiente')->count();

        $documentosPendientes = DocumentoPostulante::where('estado', 'pendiente')->count();
        $documentosObservados = DocumentoPostulante::where('estado', 'observado')->count();
        $documentosValidados = DocumentoPostulante::where('estado', 'validado')->count();

        $totalDocentes = Docente::count();

        $asignacionesAcademicas = AsignacionAcademica::whereIn('grupo_id', $grupoIds)
            ->where('estado', 'activa')
            ->count();

        $materiasActivas = Materia::where('activo', true)->count();

        $totalEvaluaciones = Examen::whereHas('postulante', function ($query) use ($grupoIds) {
                $query->whereIn('grupo_id', $grupoIds);
            })
            ->count();

        $aprobadosMateria = Examen::whereHas('postulante', function ($query) use ($grupoIds) {
                $query->whereIn('grupo_id', $grupoIds);
            })
            ->where('estado', 'aprobado')
            ->count();

        $reprobadosMateria = Examen::whereHas('postulante', function ($query) use ($grupoIds) {
                $query->whereIn('grupo_id', $grupoIds);
            })
            ->where('estado', 'reprobado')
            ->count();

        $promedioGeneral = Examen::whereHas('postulante', function ($query) use ($grupoIds) {
                $query->whereIn('grupo_id', $grupoIds);
            })
            ->avg('promedio_final');

        $promedioGeneral = $promedioGeneral ? round($promedioGeneral, 2) : 0;

        $aceptadosPrimera = AsignacionCupo::whereHas('postulante', function ($query) use ($grupoIds) {
                $query->whereIn('grupo_id', $grupoIds);
            })
            ->where('estado', 'primera_opcion')
            ->count();

        $aceptadosSegunda = AsignacionCupo::whereHas('postulante', function ($query) use ($grupoIds) {
                $query->whereIn('grupo_id', $grupoIds);
            })
            ->where('estado', 'segunda_opcion')
            ->count();

        $aprobadosSinCupo = AsignacionCupo::whereHas('postulante', function ($query) use ($grupoIds) {
                $query->whereIn('grupo_id', $grupoIds);
            })
            ->where('estado', 'aprobado_sin_cupo')
            ->count();

        $reprobadosFinal = AsignacionCupo::whereHas('postulante', function ($query) use ($grupoIds) {
                $query->whereIn('grupo_id', $grupoIds);
            })
            ->where('estado', 'reprobado')
            ->count();

        $ultimosGrupos = $this->gruposBase()
            ->with(['carrera', 'asignacionesAcademicas.docente.user'])
            ->orderByDesc('id')
            ->take(8)
            ->get();

        $ultimasAsignaciones = AsignacionAcademica::with([
                'grupo.carrera',
                'docente.user',
                'materia',
                'aula',
                'horario',
            ])
            ->whereIn('grupo_id', $grupoIds)
            ->where('estado', 'activa')
            ->latest()
            ->take(8)
            ->get();

        $topResultados = AsignacionCupo::with([
                'postulante.user',
                'postulante.grupo',
                'carrera',
            ])
            ->whereHas('postulante', function ($query) use ($grupoIds) {
                $query->whereIn('grupo_id', $grupoIds);
            })
            ->orderByRaw('posicion_ranking IS NULL')
            ->orderBy('posicion_ranking')
            ->take(8)
            ->get();

        return [
            'totalGrupos' => $totalGrupos,
            'totalPostulantes' => $totalPostulantes,
            'postulantesAceptados' => $postulantesAceptados,
            'postulantesPendientes' => $postulantesPendientes,

            'documentosPendientes' => $documentosPendientes,
            'documentosObservados' => $documentosObservados,
            'documentosValidados' => $documentosValidados,

            'totalDocentes' => $totalDocentes,
            'asignacionesAcademicas' => $asignacionesAcademicas,
            'materiasActivas' => $materiasActivas,

            'totalEvaluaciones' => $totalEvaluaciones,
            'aprobadosMateria' => $aprobadosMateria,
            'reprobadosMateria' => $reprobadosMateria,
            'promedioGeneral' => $promedioGeneral,

            'aceptadosPrimera' => $aceptadosPrimera,
            'aceptadosSegunda' => $aceptadosSegunda,
            'aprobadosSinCupo' => $aprobadosSinCupo,
            'reprobadosFinal' => $reprobadosFinal,

            'ultimosGrupos' => $ultimosGrupos,
            'ultimasAsignaciones' => $ultimasAsignaciones,
            'topResultados' => $topResultados,
        ];
    }
};

?>

<div class="coord-dashboard-container">
    <style>
        /* Variables y Estilos del Sistema de Diseño Premium */
        .coord-dashboard-container {
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
        .grid-6 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .grid-4 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 24px;
            margin-bottom: 24px;
        }
        .grid-2 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
            gap: 24px;
            margin-bottom: 24px;
        }

        /* Indicadores Estadísticos KPI */
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
            background-color: #cbd5e1;
        }
        .stat-groups::before { background-color: #3b82f6; }       /* Azul */
        .stat-applicants::before { background-color: #6366f1; }   /* Índigo */
        .stat-accepted::before { background-color: #10b981; }     /* Esmeralda */
        .stat-teachers::before { background-color: #0ea5e9; }     /* Cielo */
        .stat-assignments::before { background-color: #f59e0b; }  /* Ámbar */
        .stat-average::before { background-color: #8b5cf6; }      /* Morado */

        .stat span {
            color: #64748b;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 6px;
        }
        .stat strong {
            font-size: 28px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.04em;
        }
        .stat-groups strong { color: #2563eb; }
        .stat-applicants strong { color: #4f46e5; }
        .stat-accepted strong { color: #059669; }
        .stat-assignments strong { color: #d97706; }
        .stat-average strong { color: #7c3aed; }

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

        /* Estilos de Tablas y Estructuras */
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
        .table-mini td {
            padding: 10px 0;
        }
        .table-mini tr:hover td {
            background-color: transparent;
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

        /* Badges de Estado Redondeados */
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
                <h1 style="font-size: 24px; font-weight:800; color:#0f172a; margin:0;">Dashboard Coordinador</h1>
                <p style="margin-top:4px;">
                    Panel de supervisión académica del CUP: grupos, docentes, materias, calificaciones y resultados finales.
                </p>
            </div>

            <div wire:loading class="loading-badge">
                Actualizando datos...
            </div>
        </div>
    </div>

    {{-- Fichas KPI Superiores --}}
    <div class="grid-6">
        <div class="stat stat-groups">
            <span>Grupos supervisados</span>
            <strong>{{ $totalGrupos }}</strong>
        </div>

        <div class="stat stat-applicants">
            <span>Postulantes en grupos</span>
            <strong>{{ $totalPostulantes }}</strong>
        </div>

        <div class="stat stat-accepted">
            <span>Postulantes aceptados</span>
            <strong>{{ $postulantesAceptados }}</strong>
        </div>

        <div class="stat stat-teachers">
            <span>Docentes registrados</span>
            <strong>{{ $totalDocentes }}</strong>
        </div>

        <div class="stat stat-assignments">
            <span>Asignaciones académicas</span>
            <strong>{{ $asignacionesAcademicas }}</strong>
        </div>

        <div class="stat stat-average">
            <span>Promedio general</span>
            <strong>{{ number_format($promedioGeneral, 2) }}</strong>
        </div>
    </div>

    {{-- Accesos Rápidos Convertidos en Rejilla SaaS --}}
    <div class="card">
        <h2>Accesos rápidos</h2>
        <div class="btn-group-links">
            <a href="{{ route('reportes.postulantes.lista-general') }}" class="btn-quick-link">
                <span>Lista de postulantes</span> ➜
            </a>

            @if (Route::has('docentes.index'))
                <a href="{{ route('docentes.index') }}" class="btn-quick-link">
                    <span>Ver docentes</span> ➜
                </a>
            @endif

            @if (Route::has('admin.grupos'))
                <a href="{{ route('admin.grupos') }}" class="btn-quick-link">
                    <span>Grupos habilitados</span> ➜
                </a>
            @endif

            @if (Route::has('admin.asignacion-academica'))
                <a href="{{ route('admin.asignacion-academica') }}" class="btn-quick-link">
                    <span>Asignación académica</span> ➜
                </a>
            @endif

            @if (Route::has('admin.estadisticas-calificaciones'))
                <a href="{{ route('admin.estadisticas-calificaciones') }}" class="btn-quick-link">
                    <span>Estadísticas CU12</span> ➜
                </a>
            @endif

            @if (Route::has('admin.asignacion-cupos'))
                <a href="{{ route('admin.asignacion-cupos') }}" class="btn-quick-link">
                    <span>Asignación de cupos</span> ➜
                </a>
            @endif
        </div>
    </div>

    {{-- Bloques de Resumen Modulares en 4 Columnas --}}
    <div class="grid-4">
        
        <!-- Bloque 1: Control de Documentos -->
        <div class="card" style="margin-bottom:0; padding:18px;">
            <h2>Documentos</h2>
            <div class="table-responsive" style="border:none;">
                <table class="table table-mini">
                    <tbody>
                        <tr>
                            <td style="color:#475569; font-weight:500;">Validados</td>
                            <td style="text-align:right; font-weight:700; color:#10b981;">{{ $documentosValidados }}</td>
                        </tr>
                        <tr>
                            <td style="color:#475569; font-weight:500;">Pendientes</td>
                            <td style="text-align:right; font-weight:700; color:#2563eb;">{{ $documentosPendientes }}</td>
                        </tr>
                        <tr>
                            <td style="color:#475569; font-weight:500;">Observados</td>
                            <td style="text-align:right; font-weight:700; color:#f59e0b;">{{ $documentosObservados }}</td>
                        </tr>
                        <tr>
                            <td style="color:#475569; font-weight:500;">Postulantes pend.</td>
                            <td style="text-align:right; font-weight:700; color:#64748b;">{{ $postulantesPendientes }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Bloque 2: Calificaciones Generales -->
        <div class="card" style="margin-bottom:0; padding:18px;">
            <h2>Calificaciones</h2>
            <div class="table-responsive" style="border:none;">
                <table class="table table-mini">
                    <tbody>
                        <tr>
                            <td style="color:#475569; font-weight:500;">Total evaluaciones</td>
                            <td style="text-align:right; font-weight:700; color:#0f172a;">{{ $totalEvaluaciones }}</td>
                        </tr>
                        <tr>
                            <td style="color:#475569; font-weight:500;">Aprobados por mat.</td>
                            <td style="text-align:right; font-weight:700; color:#10b981;">{{ $aprobadosMateria }}</td>
                        </tr>
                        <tr>
                            <td style="color:#475569; font-weight:500;">Reprobados por mat.</td>
                            <td style="text-align:right; font-weight:700; color:#ef4444;">{{ $reprobadosMateria }}</td>
                        </tr>
                        <tr>
                            <td style="color:#475569; font-weight:500;">Materias activas</td>
                            <td style="text-align:right; font-weight:700; color:#6366f1;">{{ $materiasActivas }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Bloque 3: Resumen del Resultado Final -->
        <div class="card" style="margin-bottom:0; padding:18px;">
            <h2>Resultado final</h2>
            <div class="table-responsive" style="border:none;">
                <table class="table table-mini">
                    <tbody>
                        <tr>
                            <td style="color:#475569; font-weight:500;">Aceptados 1ra opc.</td>
                            <td style="text-align:right; font-weight:700; color:#10b981;">{{ $aceptadosPrimera }}</td>
                        </tr>
                        <tr>
                            <td style="color:#475569; font-weight:500;">Aceptados 2da opc.</td>
                            <td style="text-align:right; font-weight:700; color:#3b82f6;">{{ $aceptadosSegunda }}</td>
                        </tr>
                        <tr>
                            <td style="color:#475569; font-weight:500;">Aprobados sin cupo</td>
                            <td style="text-align:right; font-weight:700; color:#f59e0b;">{{ $aprobadosSinCupo }}</td>
                        </tr>
                        <tr>
                            <td style="color:#475569; font-weight:500;">Reprobados finales</td>
                            <td style="text-align:right; font-weight:700; color:#ef4444;">{{ $reprobadosFinal }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Bloque 4: Control Académico Consolidado -->
        <div class="card" style="margin-bottom:0; padding:18px;">
            <h2>Control académico</h2>
            <div class="table-responsive" style="border:none;">
                <table class="table table-mini">
                    <tbody>
                        <tr>
                            <td style="color:#475569; font-weight:500;">Grupos supervisados</td>
                            <td style="text-align:right; font-weight:700; color:#0f172a;">{{ $totalGrupos }}</td>
                        </tr>
                        <tr>
                            <td style="color:#475569; font-weight:500;">Docentes registrados</td>
                            <td style="text-align:right; font-weight:700; color:#475569;">{{ $totalDocentes }}</td>
                        </tr>
                        <tr>
                            <td style="color:#475569; font-weight:500;">Asignaciones activas</td>
                            <td style="text-align:right; font-weight:700; color:#6366f1;">{{ $asignacionesAcademicas }}</td>
                        </tr>
                        <tr>
                            <td style="color:#475569; font-weight:500;">Postulantes aceptados</td>
                            <td style="text-align:right; font-weight:700; color:#10b981;">{{ $postulantesAceptados }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Listado Completo: Grupos Supervisados --}}
    <div class="card">
        <div style="padding-bottom: 12px; border-bottom: 1px solid #f1f5f9; margin-bottom: 16px;">
            <h2 style="margin:0;">Grupos supervisados</h2>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Grupo</th>
                        <th>Carrera</th>
                        <th style="text-align:center; width:100px;">Cupos</th>
                        <th style="text-align:center; width:140px;">Postulantes</th>
                        <th style="text-align:center; width:200px;">Asignaciones académicas</th>
                        <th style="text-align:center; width:120px;">Estado</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($ultimosGrupos as $grupo)
                        <tr>
                            <td>
                                <strong style="color: #0f172a; font-size:14.5px;">{{ $grupo->nombre }}</strong>
                                <span class="subtext">{{ $grupo->gestion ?? '' }}</span>
                            </td>

                            <td>
                                <span style="font-weight: 500; color:#475569;">{{ $grupo->carrera->nombre ?? 'Sin carrera' }}</span>
                            </td>

                            <td style="text-align:center; font-weight:600; color:#64748b;">
                                {{ $grupo->cupos_maximo ?? 70 }}
                            </td>

                            <td style="text-align:center; font-weight:700; color:#2563eb;">
                                {{ $grupo->postulantes()->count() }}
                            </td>

                            <td style="text-align:center; font-weight:600; color:#4f46e5;">
                                {{ $grupo->asignacionesAcademicas()->where('estado', 'activa')->count() }}
                            </td>

                            <td style="text-align:center;">
                                @if ($grupo->activo)
                                    <span class="badge badge-aprobado">Activo</span>
                                @else
                                    <span class="badge badge-rechazado">Inactivo</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center; padding:32px; color:#94a3b8; font-weight: 500;">
                                No existen grupos supervisados registrados en el sistema.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Secciones Cruzadas de Cierre en 2 Columnas --}}
    <div class="grid-2">
        
        <!-- Últimas Asignaciones Académicas -->
        <div class="card" style="margin-bottom:0;">
            <div style="padding-bottom: 12px; border-bottom: 1px solid #f1f5f9; margin-bottom: 16px;">
                <h2>Últimas asignaciones académicas</h2>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Docente</th>
                            <th>Grupo</th>
                            <th>Materia</th>
                            <th>Horario</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($ultimasAsignaciones as $asignacion)
                            <tr>
                                <td>
                                    <strong style="color:#0f172a;">
                                        {{ $asignacion->docente->user->name ?? '' }} {{ $asignacion->docente->user->apellido ?? '' }}
                                    </strong>
                                </td>

                                <td>
                                    <span style="font-weight:600; color:#334155;">{{ $asignacion->grupo->nombre ?? 'Sin grupo' }}</span>
                                </td>

                                <td>
                                    <strong style="color: #1e40af; background: #eff6ff; padding: 4px 8px; border-radius: 6px; font-size:12.5px;">
                                        {{ $asignacion->materia->nombre ?? 'Sin materia' }}
                                    </strong>
                                </td>

                                <td>
                                    <strong style="color: #0f172a; font-size:13.5px;">{{ ucfirst($asignacion->horario->dia ?? '') }}</strong>
                                    <span class="subtext" style="color: #16a34a; font-weight:500;">
                                        {{ ucfirst($asignacion->horario->turno ?? '') }} | {{ $asignacion->horario->hora_inicio ?? '' }} - {{ $asignacion->horario->hora_final ?? '' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align:center; padding:32px; color:#94a3b8; font-weight: 500;">
                                    No existen asignaciones académicas registradas recientemente.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top Resultados de Admisión -->
        <div class="card" style="margin-bottom:0;">
            <div style="padding-bottom: 12px; border-bottom: 1px solid #f1f5f9; margin-bottom: 16px;">
                <h2>Top resultados de admisión</h2>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="text-align:center; width:80px;">Ranking</th>
                            <th>Postulante</th>
                            <th style="text-align:center; width:90px;">Promedio</th>
                            <th style="text-align:center; width:130px;">Resultado</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($topResultados as $resultado)
                            <tr>
                                <td style="text-align:center;">
                                    <span style="background: #f1f5f9; padding: 4px 8px; border-radius: 6px; font-weight: 700; color: #1e293b;">
                                        #{{ $resultado->posicion_ranking ?? '-' }}
                                    </span>
                                </td>

                                <td>
                                    <strong style="color:#0f172a;">
                                        {{ $resultado->postulante->user->name ?? '' }} {{ $resultado->postulante->user->apellido ?? '' }}
                                    </strong>
                                    <span class="subtext">{{ $resultado->postulante->grupo->nombre ?? 'Sin grupo' }}</span>
                                </td>

                                <td style="text-align:center;">
                                    <strong style="color:#111827; font-size:15px;">{{ number_format($resultado->promedio_final, 2) }}</strong>
                                </td>

                                <td style="text-align:center;">
                                    @if ($resultado->estado === 'primera_opcion')
                                        <span class="badge badge-aprobado">1ra opción</span>
                                    @elseif ($resultado->estado === 'segunda_opcion')
                                        <span class="badge badge-aprobado">2da opción</span>
                                    @elseif ($resultado->estado === 'aprobado_sin_cupo')
                                        <span class="badge badge-rechazado">Sin cupo</span>
                                    @elseif ($resultado->estado === 'reprobado')
                                        <span class="badge badge-rechazado">Reprobado</span>
                                    @else
                                        <span class="badge badge-pendiente">{{ $resultado->estado }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align:center; padding:32px; color:#94a3b8; font-weight: 500;">
                                    Todavía no se ha generado la asignación oficial de cupos para este ciclo.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>