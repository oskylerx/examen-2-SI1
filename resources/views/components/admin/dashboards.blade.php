<?php

use App\Models\Postulante;
use App\Models\Pago;
use App\Models\DocumentoPostulante;
use App\Models\Grupo;
use App\Models\Docente;
use App\Models\Carrera;
use App\Models\AsignacionAcademica;
use App\Models\Examen;
use App\Models\AsignacionCupo;
use Livewire\Component;

new class extends Component
{
    public function with()
    {
        $totalPostulantes = Postulante::count();

        $postulantesPendientes = Postulante::where('estado_inscripcion', 'pendiente')->count();
        $postulantesAceptados = Postulante::where('estado_inscripcion', 'aceptado')->count();
        $postulantesObservados = Postulante::where('estado_inscripcion', 'observado')->count();
        $postulantesRechazados = Postulante::where('estado_inscripcion', 'rechazado')->count();

        $pagosPagados = Pago::where('estado', 'pagado')->count();
        $pagosPendientes = Pago::where('estado', 'pendiente')->count();
        $pagosObservados = Pago::where('estado', 'observado')->count();

        $documentosValidados = DocumentoPostulante::where('estado', 'validado')->count();
        $documentosPendientes = DocumentoPostulante::where('estado', 'pendiente')->count();
        $documentosObservados = DocumentoPostulante::where('estado', 'observado')->count();
        $documentosRechazados = DocumentoPostulante::where('estado', 'rechazado')->count();

        $totalGrupos = Grupo::count();
        $gruposActivos = Grupo::where('activo', true)->count();

        $totalDocentes = Docente::count();
        $totalCarreras = Carrera::count();

        $totalAsignacionesAcademicas = AsignacionAcademica::where('estado', 'activa')->count();

        $totalEvaluaciones = Examen::count();
        $aprobadosMateria = Examen::where('estado', 'aprobado')->count();
        $reprobadosMateria = Examen::where('estado', 'reprobado')->count();

        $promedioGeneral = Examen::count() > 0
            ? round(Examen::avg('promedio_final'), 2)
            : 0;

        $aceptadosPrimera = AsignacionCupo::where('estado', 'primera_opcion')->count();
        $aceptadosSegunda = AsignacionCupo::where('estado', 'segunda_opcion')->count();
        $aprobadosSinCupo = AsignacionCupo::where('estado', 'aprobado_sin_cupo')->count();
        $reprobadosFinal = AsignacionCupo::where('estado', 'reprobado')->count();

        $ultimosPostulantes = Postulante::with([
                'user',
                'primeraOpcionCarrera',
                'segundaOpcionCarrera',
                'grupo',
            ])
            ->latest()
            ->take(8)
            ->get();

        $ultimosResultados = AsignacionCupo::with([
                'postulante.user',
                'postulante.primeraOpcionCarrera',
                'postulante.segundaOpcionCarrera',
                'carrera',
            ])
            ->orderByRaw('posicion_ranking IS NULL')
            ->orderBy('posicion_ranking')
            ->take(8)
            ->get();

        $cuposPorCarrera = Carrera::withCount([
                'asignacionesCupo as asignados_primera' => function ($query) {
                    $query->where('estado', 'primera_opcion');
                },
                'asignacionesCupo as asignados_segunda' => function ($query) {
                    $query->where('estado', 'segunda_opcion');
                },
            ])
            ->orderBy('nombre')
            ->get();

        return [
            'totalPostulantes' => $totalPostulantes,
            'postulantesPendientes' => $postulantesPendientes,
            'postulantesAceptados' => $postulantesAceptados,
            'postulantesObservados' => $postulantesObservados,
            'postulantesRechazados' => $postulantesRechazados,

            'pagosPagados' => $pagosPagados,
            'pagosPendientes' => $pagosPendientes,
            'pagosObservados' => $pagosObservados,

            'documentosValidados' => $documentosValidados,
            'documentosPendientes' => $documentosPendientes,
            'documentosObservados' => $documentosObservados,
            'documentosRechazados' => $documentosRechazados,

            'totalGrupos' => $totalGrupos,
            'gruposActivos' => $gruposActivos,
            'totalDocentes' => $totalDocentes,
            'totalCarreras' => $totalCarreras,
            'totalAsignacionesAcademicas' => $totalAsignacionesAcademicas,

            'totalEvaluaciones' => $totalEvaluaciones,
            'aprobadosMateria' => $aprobadosMateria,
            'reprobadosMateria' => $reprobadosMateria,
            'promedioGeneral' => $promedioGeneral,

            'aceptadosPrimera' => $aceptadosPrimera,
            'aceptadosSegunda' => $aceptadosSegunda,
            'aprobadosSinCupo' => $aprobadosSinCupo,
            'reprobadosFinal' => $reprobadosFinal,

            'ultimosPostulantes' => $ultimosPostulantes,
            'ultimosResultados' => $ultimosResultados,
            'cuposPorCarrera' => $cuposPorCarrera,
        ];
    }
};

?>

<div class="admin-dashboard-container">
    <style>
        /* Variables y Estilos de Sistema Base */
        .admin-dashboard-container {
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
            font-size: 16px;
            font-weight: 700;
            margin: 0 0 16px 0;
            letter-spacing: -0.025em;
            text-transform: uppercase;
            font-size: 13px;
            color: #475569;
            letter-spacing: 0.05em;
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

        /* Indicadores Estadísticos Kpi */
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
        .stat-postulantes::before { background-color: #3b82f6; }
        .stat-aceptados::before { background-color: #10b981; }
        .stat-grupos::before { background-color: #6366f1; }
        .stat-docentes::before { background-color: #0ea5e9; }
        .stat-carreras::before { background-color: #f59e0b; }
        .stat-promedio::before { background-color: #8b5cf6; }

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

        /* Enlaces de Accesos Rápidos */
        .btn-group-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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

        /* Estilos de Tablas y Filas */
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
        .table-mini tr:hover td {
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

        /* Elementos de carga */
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
                <h1 style="font-size: 24px; font-weight:800; color:#0f172a; margin:0;">Dashboard Administrador</h1>
                <p style="margin-top:4px;">
                    Resumen general del proceso CUP: inscripción, pagos, documentos, grupos, calificaciones y asignación final de cupos.
                </p>
            </div>

            <div wire:loading class="loading-badge">
                Actualizando datos...
            </div>
        </div>
    </div>

    {{-- Fichas KPI Superiores --}}
    <div class="grid-6">
        <div class="stat stat-postulantes">
            <span>Total postulantes</span>
            <strong>{{ $totalPostulantes }}</strong>
        </div>

        <div class="stat stat-aceptados">
            <span>Postulantes aceptados</span>
            <strong style="color: #10b981;">{{ $postulantesAceptados }}</strong>
        </div>

        <div class="stat stat-grupos">
            <span>Grupos activos</span>
            <strong>{{ $gruposActivos }}</strong>
        </div>

        <div class="stat stat-docentes">
            <span>Docentes</span>
            <strong>{{ $totalDocentes }}</strong>
        </div>

        <div class="stat stat-carreras">
            <span>Carreras</span>
            <strong>{{ $totalCarreras }}</strong>
        </div>

        <div class="stat stat-promedio">
            <span>Promedio general</span>
            <strong style="color: #8b5cf6;">{{ number_format($promedioGeneral, 2) }}</strong>
        </div>
    </div>

    {{-- Accesos Rápidos Convertidos en Botones SaaS --}}
    <div class="card">
        <h2>Accesos rápidos al sistema</h2>
        <div class="btn-group-links">
            <a href="{{ route('postulantes.index') }}" class="btn-quick-link">
                <span>Gestionar postulantes</span> ➜
            </a>

            <a href="{{ route('admin.grupos') }}" class="btn-quick-link">
                <span>Grupos habilitados</span> ➜
            </a>

            <a href="{{ route('admin.asignacion-academica') }}" class="btn-quick-link">
                <span>Asignación académica</span> ➜
            </a>

            <a href="{{ route('admin.asignacion-cupos') }}" class="btn-quick-link">
                <span>Asignación de cupos</span> ➜
            </a>

            <a href="{{ route('admin.estadisticas-calificaciones') }}" class="btn-quick-link">
                <span>Estadísticas CU12</span> ➜
            </a>

            <a href="{{ route('reportes.postulantes.lista-general') }}" class="btn-quick-link">
                <span>Reporte de postulantes</span> ➜
            </a>
        </div>
    </div>

    {{-- Tablas de Resumen Intermedio en 4 Columnas --}}
    <div class="grid-4">
        
        <!-- Bloque 1: Estado de Inscripción -->
        <div class="card" style="margin-bottom:0; padding:18px;">
            <h2>Estado de inscripción</h2>
            <div class="table-responsive" style="border:none;">
                <table class="table table-mini">
                    <tbody>
                        <tr>
                            <td style="padding:10px 0; color:#475569; font-weight:500;">Pendientes</td>
                            <td style="text-align:right; padding:10px 0; font-weight:700; color:#1e293b;">{{ $postulantesPendientes }}</td>
                        </tr>
                        <tr>
                            <td style="padding:10px 0; color:#475569; font-weight:500;">Aceptados</td>
                            <td style="text-align:right; padding:10px 0; font-weight:700; color:#10b981;">{{ $postulantesAceptados }}</td>
                        </tr>
                        <tr>
                            <td style="padding:10px 0; color:#475569; font-weight:500;">Observados</td>
                            <td style="text-align:right; padding:10px 0; font-weight:700; color:#f59e0b;">{{ $postulantesObservados }}</td>
                        </tr>
                        <tr>
                            <td style="padding:10px 0; color:#475569; font-weight:500;">Rechazados</td>
                            <td style="text-align:right; padding:10px 0; font-weight:700; color:#ef4444;">{{ $postulantesRechazados }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Bloque 2: Pagos y Documentos -->
        <div class="card" style="margin-bottom:0; padding:18px;">
            <h2>Pagos y documentos</h2>
            <div class="table-responsive" style="border:none;">
                <table class="table table-mini">
                    <tbody>
                        <tr>
                            <td style="padding:10px 0; color:#475569; font-weight:500;">Pagos pagados</td>
                            <td style="text-align:right; padding:10px 0; font-weight:700; color:#10b981;">{{ $pagosPagados }}</td>
                        </tr>
                        <tr>
                            <td style="padding:10px 0; color:#475569; font-weight:500;">Pagos pendientes</td>
                            <td style="text-align:right; padding:10px 0; font-weight:700; color:#64748b;">{{ $pagosPendientes }}</td>
                        </tr>
                        <tr>
                            <td style="padding:10px 0; color:#475569; font-weight:500;">Documentos validados</td>
                            <td style="text-align:right; padding:10px 0; font-weight:700; color:#2563eb;">{{ $documentosValidados }}</td>
                        </tr>
                        <tr>
                            <td style="padding:10px 0; color:#475569; font-weight:500;">Documentos observados</td>
                            <td style="text-align:right; padding:10px 0; font-weight:700; color:#f59e0b;">{{ $documentosObservados }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Bloque 3: Calificaciones -->
        <div class="card" style="margin-bottom:0; padding:18px;">
            <h2>Calificaciones</h2>
            <div class="table-responsive" style="border:none;">
                <table class="table table-mini">
                    <tbody>
                        <tr>
                            <td style="padding:10px 0; color:#475569; font-weight:500;">Total evaluaciones</td>
                            <td style="text-align:right; padding:10px 0; font-weight:700; color:#0f172a;">{{ $totalEvaluaciones }}</td>
                        </tr>
                        <tr>
                            <td style="padding:10px 0; color:#475569; font-weight:500;">Aprobados por mat.</td>
                            <td style="text-align:right; padding:10px 0; font-weight:700; color:#10b981;">{{ $aprobadosMateria }}</td>
                        </tr>
                        <tr>
                            <td style="padding:10px 0; color:#475569; font-weight:500;">Reprobados por mat.</td>
                            <td style="text-align:right; padding:10px 0; font-weight:700; color:#ef4444;">{{ $reprobadosMateria }}</td>
                        </tr>
                        <tr>
                            <td style="padding:10px 0; color:#475569; font-weight:500;">Asignaciones acad.</td>
                            <td style="text-align:right; padding:10px 0; font-weight:700; color:#6366f1;">{{ $totalAsignacionesAcademicas }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Bloque 4: Resultado Final -->
        <div class="card" style="margin-bottom:0; padding:18px;">
            <h2>Resultado final</h2>
            <div class="table-responsive" style="border:none;">
                <table class="table table-mini">
                    <tbody>
                        <tr>
                            <td style="padding:10px 0; color:#475569; font-weight:500;">Aceptados 1ra opc.</td>
                            <td style="text-align:right; padding:10px 0; font-weight:700; color:#10b981;">{{ $aceptadosPrimera }}</td>
                        </tr>
                        <tr>
                            <td style="padding:10px 0; color:#475569; font-weight:500;">Aceptados 2da opc.</td>
                            <td style="text-align:right; padding:10px 0; font-weight:700; color:#3b82f6;">{{ $aceptadosSegunda }}</td>
                        </tr>
                        <tr>
                            <td style="padding:10px 0; color:#475569; font-weight:500;">Aprobados sin cupo</td>
                            <td style="text-align:right; padding:10px 0; font-weight:700; color:#f59e0b;">{{ $aprobadosSinCupo }}</td>
                        </tr>
                        <tr>
                            <td style="padding:10px 0; color:#475569; font-weight:500;">Reprobados final</td>
                            <td style="text-align:right; padding:10px 0; font-weight:700; color:#ef4444;">{{ $reprobadosFinal }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Tabla Cupos por Carrera --}}
    <div class="card">
        <div style="padding-bottom: 12px; border-bottom: 1px solid #f1f5f9; margin-bottom: 16px;">
            <h2 style="margin:0;">Cupos por carrera</h2>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Carrera</th>
                        <th style="text-align:center; width:100px;">Cupos</th>
                        <th style="text-align:center; width:160px;">Asignados 1ra opc.</th>
                        <th style="text-align:center; width:160px;">Asignados 2da opc.</th>
                        <th style="text-align:center; width:150px;">Total asignados</th>
                        <th style="text-align:center; width:120px;">Disponibles</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($cuposPorCarrera as $carrera)
                        @php
                            $totalAsignados = ($carrera->asignados_primera ?? 0) + ($carrera->asignados_segunda ?? 0);
                            $disponibles = max(0, ($carrera->cupos ?? 0) - $totalAsignados);
                        @endphp

                        <tr>
                            <td>
                                <strong style="color: #1e293b; font-size:14.5px;">{{ $carrera->codigo_carrera ?? '' }} - {{ $carrera->nombre }}</strong>
                            </td>

                            <td style="text-align:center; font-weight:600; color:#475569;">
                                {{ $carrera->cupos ?? 0 }}
                            </td>

                            <td style="text-align:center; color:#334155;">
                                {{ $carrera->asignados_primera ?? 0 }}
                            </td>

                            <td style="text-align:center; color:#64748b;">
                                {{ $carrera->asignados_segunda ?? 0 }}
                            </td>

                            <td style="text-align:center; color:#2563eb;">
                                <strong>{{ $totalAsignados }}</strong>
                            </td>

                            <td style="text-align:center;">
                                <span style="font-weight: 700; color: {{ $disponibles > 0 ? '#16a34a' : '#94a3b8' }};">
                                    {{ $disponibles }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center; padding:32px; color:#94a3b8; font-weight: 500;">
                                No existen carreras registradas en la base de datos.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Fichas Inferiores Cruzadas en 2 Columnas --}}
    <div class="grid-2">
        
        <!-- Últimos Postulantes Registrados -->
        <div class="card" style="margin-bottom:0;">
            <div style="padding-bottom: 12px; border-bottom: 1px solid #f1f5f9; margin-bottom: 16px;">
                <h2>Últimos postulantes registrados</h2>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Postulante</th>
                            <th>CI</th>
                            <th>Estado</th>
                            <th>Grupo</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($ultimosPostulantes as $postulante)
                            <tr>
                                <td>
                                    <strong style="color:#0f172a;">
                                        {{ $postulante->user->name ?? '' }} {{ $postulante->user->apellido ?? '' }}
                                    </strong>
                                    <span class="subtext">{{ $postulante->user->username ?? '' }}</span>
                                </td>

                                <td style="font-weight: 500; color:#475569;">{{ $postulante->user->ci ?? '-' }}</td>

                                <td>
                                    @if ($postulante->estado_inscripcion === 'aceptado')
                                        <span class="badge badge-aprobado">Aceptado</span>
                                    @elseif ($postulante->estado_inscripcion === 'rechazado')
                                        <span class="badge badge-rechazado">Rechazado</span>
                                    @else
                                        <span class="badge badge-pendiente">
                                            {{ ucfirst($postulante->estado_inscripcion ?? 'pendiente') }}
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    <span style="font-weight:600; color:#334155;">{{ $postulante->grupo->nombre ?? 'Sin grupo' }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align:center; padding:32px; color:#94a3b8; font-weight: 500;">
                                    No existen postulantes registrados recientemente.
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
                        @forelse ($ultimosResultados as $resultado)
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
                                    <span class="subtext">{{ $resultado->carrera->nombre ?? 'Sin carrera' }}</span>
                                </td>

                                <td style="text-align:center;">
                                    <strong style="color:#111827; font-size:15px;">{{ number_format($resultado->promedio_final, 2) }}</strong>
                                </td>

                                <td style="text-align:center;">
                                    @if (in_array($resultado->estado, ['primera_opcion', 'segunda_opcion']))
                                        <span class="badge badge-aprobado">Admitido</span>
                                    @elseif ($resultado->estado === 'aprobado_sin_cupo')
                                        <span class="badge badge-pendiente">Sin cupo</span>
                                    @else
                                        <span class="badge badge-rechazado">Reprobado</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align:center; padding:32px; color:#94a3b8; font-weight: 500;">
                                    Todavía no se ha ejecutado el proceso oficial de asignación de cupos.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>