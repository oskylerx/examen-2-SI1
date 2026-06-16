<?php

use App\Models\Postulante;
use App\Models\Examen;
use Livewire\Component;

new class extends Component
{
    public $postulante = null;

    public function mount()
    {
        $this->postulante = Postulante::with([
            'user',
            'pagos',
            'documentos',
            'grupo.carrera',
            'primeraOpcionCarrera',
            'segundaOpcionCarrera',
            'asignacionCupo.carrera',
        ])
            ->where('user_id', auth()->id())
            ->first();

        if (! $this->postulante) {
            session()->flash('mensaje', 'No se encontró información del postulante.');
        }
    }

    private function obtenerNota($examen, $tipo)
    {
        return optional($examen->calificaciones->firstWhere('tipo', $tipo))->nota;
    }

    public function with()
    {
        $examenes = collect();

        if ($this->postulante) {
            $examenes = Examen::with([
                'materia',
                'calificaciones',
            ])
                ->where('postulante_id', $this->postulante->id)
                ->orderBy('materia_id')
                ->get();
        }

        $pago = $this->postulante
            ? $this->postulante->pagos->sortByDesc('fecha_pago')->first()
            : null;

        $documentos = $this->postulante
            ? $this->postulante->documentos
            : collect();

        $documentosValidados = $documentos
            ->whereIn('tipo', [
                'titulo_bachiller',
                'cedula_identidad',
                'boletin_sexto',
                'comprobante_pago',
            ])
            ->where('estado', 'validado')
            ->count();

        $totalDocumentosRequeridos = 4;

        $materiasAprobadas = $examenes->where('estado', 'aprobado')->count();
        $materiasReprobadas = $examenes->where('estado', 'reprobado')->count();

        $promedioGeneral = $examenes->count() > 0
            ? round($examenes->avg('promedio_final'), 2)
            : 0;

        return [
            'examenes' => $examenes,
            'pago' => $pago,
            'documentos' => $documentos,
            'documentosValidados' => $documentosValidados,
            'totalDocumentosRequeridos' => $totalDocumentosRequeridos,
            'materiasAprobadas' => $materiasAprobadas,
            'materiasReprobadas' => $materiasReprobadas,
            'promedioGeneral' => $promedioGeneral,
        ];
    }
};

?>

<div class="postulante-dashboard">
    <style>
        /* Variables de Sistema de Diseño */
        .postulante-dashboard {
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
            font-size: 18px;
            font-weight: 700;
            margin: 0 0 16px 0;
            letter-spacing: -0.025em;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 12px;
        }
        p {
            color: #64748b;
            font-size: 14px;
            margin: 0;
            line-height: 1.5;
        }

        /* Panel de Indicadores de Rendimiento y Estados (6 columnas dinámicas) */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
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
        }
        .stat-enrollment::before { background-color: #6366f1; } /* Índigo */
        .stat-payment::before { background-color: #0ea5e9; }    /* Cielo */
        .stat-docs::before { background-color: #475569; }       /* Pizarra */
        .stat-gpa::before { background-color: #8b5cf6; }        /* Morado */
        .stat-passed::before { background-color: #10b981; }     /* Esmeralda */
        .stat-failed::before { background-color: #f43f5e; }     /* Rosa */

        .stat span {
            color: #64748b;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 6px;
        }
        .stat strong {
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -0.04em;
        }
        .stat-enrollment strong { color: #4f46e5; }
        .stat-payment strong { color: #0284c7; }
        .stat-docs strong { color: #334155; }
        .stat-gpa strong { color: #7c3aed; }
        .stat-passed strong { color: #059669; }
        .stat-failed strong { color: #e11d48; }

        /* Dos columnas para datos de perfil */
        .profile-layout {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
            gap: 24px;
            margin-bottom: 24px;
        }

        /* Estructura para Tablas Horizontales y de Fichas Técnicas */
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
        
        /* Cabeceras de Tablas Convencionales */
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

        /* Estilos específicos para las tablas verticales de datos personales/admisión */
        .table-vertical td, .table-vertical th {
            border-bottom: 1px solid #f1f5f9;
            padding: 14px 16px;
        }
        .table-vertical th {
            background: #f8fafc;
            color: #475569;
            font-weight: 600;
            text-align: left;
            width: 35%;
            font-size: 13px;
        }
        .table-vertical td {
            color: #0f172a;
            font-weight: 500;
        }
        .table-vertical tr:last-child th, .table-vertical tr:last-child td {
            border-bottom: none;
        }

        .table-horizontal tr:last-child td {
            border-bottom: none;
        }
        .table-horizontal tr:hover td {
            background-color: #f8fafc;
        }

        /* Subtextos en celdas */
        .subtext {
            color: #64748b;
            font-size: 12px;
            display: block;
            margin-top: 2px;
        }

        /* Badges de Estado Redondeados */
        .badge {
            padding: 5px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
            text-align: center;
        }
        .badge-aprobado { background-color: #dcfce7; color: #15803d; }     /* Activa / Aprobado / Validado */
        .badge-pendiente { background-color: #e0f2fe; color: #0369a1; }    /* Finalizada / 2da Opción / Observado */
        .badge-rechazado { background-color: #fee2e2; color: #b91c1c; }    /* Inactiva / Reprobado / Rechazado */

        /* Indicadores de carga y Alertas */
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
                <h2 style="font-size: 24px; border: none; padding: 0; margin: 0;">Panel Informativo del Postulante</h2>
                <p style="margin-top: 4px;">
                    Consulte el estado de su inscripción, documentos, grupo, calificaciones y resultado final de admisión.
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

    {{-- Manejo de Estado Vacío --}}
    @if (! $postulante)
        <div class="card" style="text-align: center; padding: 40px 20px;">
            <span style="font-size: 42px; display: block; margin-bottom: 12px;">👤</span>
            <p style="color:#94a3b8; font-weight: 500;">
                No existe información registrada para este usuario en el sistema.
            </p>
        </div>
    @else

        {{-- Bloques Estadísticos e Indicadores Rápidos --}}
        <div class="grid">
            <div class="stat stat-enrollment">
                <span>Estado inscripción</span>
                <strong>{{ ucfirst($postulante->estado_inscripcion) }}</strong>
            </div>

            <div class="stat stat-payment">
                <span>Pago CUP</span>
                <strong style="font-size: 18px; text-transform: uppercase; margin-top: 4px;">{{ $pago->estado ?? 'Sin pago' }}</strong>
            </div>

            <div class="stat stat-docs">
                <span>Documentos validados</span>
                <strong>{{ $documentosValidados }}/{{ $totalDocumentosRequeridos }}</strong>
            </div>

            <div class="stat stat-gpa">
                <span>Promedio general</span>
                <strong>{{ number_format($promedioGeneral, 2) }}</strong>
            </div>

            <div class="stat stat-passed">
                <span>Materias aprobadas</span>
                <strong>{{ $materiasAprobadas }}</strong>
            </div>

            <div class="stat stat-failed">
                <span>Materias reprobadas</span>
                <strong>{{ $materiasReprobadas }}</strong>
            </div>
        </div>

        {{-- Layout de Fichas Informativas en 2 Columnas --}}
        <div class="profile-layout">
            
            {{-- Columna 1: Datos Personales --}}
            <div class="card" style="margin-bottom: 0;">
                <h2>Datos del Postulante</h2>

                <div class="table-responsive">
                    <table class="table table-vertical">
                        <tbody>
                            <tr>
                                <th>Nombre completo</th>
                                <td>{{ $postulante->user->name ?? '' }} {{ $postulante->user->apellido ?? '' }}</td>
                            </tr>
                            <tr>
                                <th>Usuario</th>
                                <td style="color: #2563eb; font-family: monospace;">{{ $postulante->user->username ?? '' }}</td>
                            </tr>
                            <tr>
                                <th>CI</th>
                                <td>{{ $postulante->user->ci ?? '' }}</td>
                            </tr>
                            <tr>
                                <th>Correo</th>
                                <td style="font-weight: 400; color: #475569;">{{ $postulante->user->email ?? '' }}</td>
                            </tr>
                            <tr>
                                <th>Ciudad</th>
                                <td>{{ $postulante->ciudad ?? 'No registrado' }}</td>
                            </tr>
                            <tr>
                                <th>Colegio</th>
                                <td>{{ $postulante->colegio ?? 'No registrado' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Columna 2: Estado de Admisión --}}
            <div class="card" style="margin-bottom: 0;">
                <h2>Estado de Admisión</h2>

                <div class="table-responsive">
                    <table class="table table-vertical">
                        <tbody>
                            <tr>
                                <th>Primera opción</th>
                                <td><span style="color: #334155;">{{ $postulante->primeraOpcionCarrera->nombre ?? 'No registrada' }}</span></td>
                            </tr>
                            <tr>
                                <th>Segunda opción</th>
                                <td><span style="color: #64748b;">{{ $postulante->segundaOpcionCarrera->nombre ?? 'No registrada' }}</span></td>
                            </tr>
                            <tr>
                                <th>Grupo asignado</th>
                                <td>
                                    @if ($postulante->grupo)
                                        <span style="color: #0f172a; font-weight: 700;">{{ $postulante->grupo->nombre }}</span>
                                        <span class="subtext">{{ $postulante->grupo->carrera->nombre ?? '' }}</span>
                                    @else
                                        <span style="color:#94a3b8; font-style: italic; font-weight: 400;">Sin grupo asignado</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Resultado final</th>
                                <td>
                                    @if ($postulante->asignacionCupo)
                                        @if ($postulante->asignacionCupo->estado === 'primera_opcion')
                                            <span class="badge badge-aprobado">Aceptado en primera opción</span>
                                        @elseif ($postulante->asignacionCupo->estado === 'segunda_opcion')
                                            <span class="badge badge-pendiente">Aceptado en segunda opción</span>
                                        @elseif ($postulante->asignacionCupo->estado === 'aprobado_sin_cupo')
                                            <span class="badge badge-rechazado">Aprobado sin cupo</span>
                                        @elseif ($postulante->asignacionCupo->estado === 'reprobado')
                                            <span class="badge badge-rechazado">Reprobado</span>
                                        @else
                                            <span class="badge badge-pendiente">{{ $postulante->asignacionCupo->estado }}</span>
                                        @endif
                                    @else
                                        <span class="badge badge-pendiente" style="background: #f1f5f9; color: #475569;">Resultado pendiente</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Carrera asignada</th>
                                <td>
                                    @if ($postulante->asignacionCupo && $postulante->asignacionCupo->carrera)
                                        <strong style="color: #1e40af; background: #eff6ff; padding: 4px 8px; border-radius: 6px; font-size: 13px;">
                                            {{ $postulante->asignacionCupo->carrera->nombre }}
                                        </strong>
                                    @else
                                        <span style="color:#94a3b8; font-style: italic; font-weight: 400;">Sin carrera asignada</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Ranking</th>
                                <td>
                                    @if(isset($postulante->asignacionCupo->posicion_ranking))
                                        <span style="background: #f1f5f9; padding: 4px 10px; border-radius: 6px; font-weight: 700; color: #1e293b;">
                                            #{{ $postulante->asignacionCupo->posicion_ranking }}
                                        </span>
                                    @else
                                        <span style="color:#cbd5e1;">-</span>
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Bloque Inferior 1: Documentos Presentados --}}
        <div class="card">
            <h2>Documentos Presentados</h2>

            <div class="table-responsive">
                <table class="table table-horizontal">
                    <thead>
                        <tr>
                            <th>Documento</th>
                            <th>Tipo</th>
                            <th>Fecha subida</th>
                            <th style="text-align:center; width: 140px;">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($documentos as $documento)
                            <tr>
                                <td><strong style="color: #334155;">{{ $documento->nombre }}</strong></td>
                                <td><span style="text-transform: capitalize; color: #64748b; font-size: 13px;">{{ str_replace('_', ' ', $documento->tipo) }}</span></td>
                                <td><span style="color: #475569; font-size: 13px; font-weight: 500;">{{ $documento->fecha_subida ?? '-' }}</span></td>
                                <td style="text-align:center;">
                                    @if ($documento->estado === 'validado')
                                        <span class="badge badge-aprobado">Validado</span>
                                    @elseif ($documento->estado === 'observado')
                                        <span class="badge badge-pendiente">Observado</span>
                                    @elseif ($documento->estado === 'rechazado')
                                        <span class="badge badge-rechazado">Rechazado</span>
                                    @else
                                        <span class="badge badge-pendiente">Pendiente</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align:center; padding:32px; color:#94a3b8; font-weight: 500;">
                                    No existen documentos registrados hasta la fecha.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Bloque Inferior 2: Calificaciones por Materia --}}
        <div class="card" style="margin-bottom: 0;">
            <h2>Calificaciones por Materia</h2>

            <div class="table-responsive">
                <table class="table table-horizontal">
                    <thead>
                        <tr>
                            <th>Materia</th>
                            <th style="text-align:center; width: 100px;">P1</th>
                            <th style="text-align:center; width: 100px;">P2</th>
                            <th style="text-align:center; width: 100px;">EF</th>
                            <th style="text-align:center; width: 120px;">Promedio</th>
                            <th style="text-align:center; width: 140px;">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($examenes as $examen)
                            @php
                                $p1 = $this->obtenerNota($examen, 'p1');
                                $p2 = $this->obtenerNota($examen, 'p2');
                                $ef = $this->obtenerNota($examen, 'ef');
                            @endphp

                            <tr>
                                <td>
                                    <strong style="color: #1e40af;">{{ $examen->materia->nombre ?? 'Sin materia' }}</strong>
                                </td>
                                <td style="text-align:center; font-weight: 500; color: #475569;">{{ $p1 ?? '-' }}</td>
                                <td style="text-align:center; font-weight: 500; color: #475569;">{{ $p2 ?? '-' }}</td>
                                <td style="text-align:center; font-weight: 500; color: #475569;">{{ $ef ?? '-' }}</td>
                                <td style="text-align:center;">
                                    @if(isset($examen->promedio_final))
                                        <span style="font-size: 15px; font-weight: 700; color: {{ $examen->estado === 'aprobado' ? '#16a34a' : '#dc2626' }};">
                                            {{ number_format($examen->promedio_final, 2) }}
                                        </span>
                                    @else
                                        <span style="color:#cbd5e1;">-</span>
                                    @endif
                                </td>
                                <td style="text-align:center;">
                                    @if ($examen->estado === 'aprobado')
                                        <span class="badge badge-aprobado">Aprobado</span>
                                    @elseif ($examen->estado === 'reprobado')
                                        <span class="badge badge-rechazado">Reprobado</span>
                                    @else
                                        <span class="badge badge-pendiente">{{ $examen->estado }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="text-align:center; padding:32px; color:#94a3b8; font-weight: 500;">
                                    Todavía no existen calificaciones oficiales registradas en su planilla.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    @endif
</div>