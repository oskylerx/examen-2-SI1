<?php

use App\Models\AsignacionCupo;
use App\Models\Carrera;
use App\Models\Examen;
use App\Models\Materia;
use App\Models\Postulante;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public $busqueda = '';
    public $carreraFiltro = '';
    public $estadoFiltro = '';
    public $porPagina = 10;

    public function updated($property)
    {
        if (in_array($property, [
            'busqueda',
            'carreraFiltro',
            'estadoFiltro',
            'porPagina',
        ])) {
            $this->resetPage();
        }
    }

    public function limpiarFiltros()
    {
        $this->busqueda = '';
        $this->carreraFiltro = '';
        $this->estadoFiltro = '';
        $this->porPagina = 10;

        $this->resetPage();
    }

    public function generarAsignacionCupos()
    {
        $materias = Materia::where('activo', true)
            ->orderBy('id')
            ->get();

        if ($materias->count() < 4) {
            session()->flash('mensaje', 'Debe tener registradas las 4 materias activas antes de asignar cupos.');
            return;
        }

        $carreras = Carrera::orderBy('id')->get();

        if ($carreras->isEmpty()) {
            session()->flash('mensaje', 'No existen carreras registradas.');
            return;
        }

        $postulantes = Postulante::with([
                'user',
                'primeraOpcionCarrera',
                'segundaOpcionCarrera',
            ])
            ->where('estado_inscripcion', 'aceptado')
            ->orderBy('id')
            ->get();

        if ($postulantes->isEmpty()) {
            session()->flash('mensaje', 'No existen postulantes aceptados para procesar.');
            return;
        }

        $materiaIds = $materias->pluck('id')->toArray();

        $aprobados = collect();
        $reprobados = collect();

        foreach ($postulantes as $postulante) {
            $examenes = Examen::where('postulante_id', $postulante->id)
                ->whereIn('materia_id', $materiaIds)
                ->get();

            $cantidadEvaluadas = $examenes->count();

            $cantidadAprobadas = $examenes
                ->where('estado', 'aprobado')
                ->count();

            $tieneTodasLasMaterias = $cantidadEvaluadas === $materias->count();

            $aproboTodasLasMaterias = $tieneTodasLasMaterias
                && $cantidadAprobadas === $materias->count();

            $promedioGeneral = $examenes->count() > 0
                ? round($examenes->avg('promedio_final'), 2)
                : 0;

            if (! $aproboTodasLasMaterias) {
                $reprobados->push([
                    'postulante' => $postulante,
                    'promedio_final' => $promedioGeneral,
                ]);

                continue;
            }

            $aprobados->push([
                'postulante' => $postulante,
                'promedio_final' => $promedioGeneral,
            ]);
        }

        $ranking = $aprobados
            ->sortByDesc('promedio_final')
            ->values();

        DB::transaction(function () use ($ranking, $reprobados, $carreras) {
            AsignacionCupo::query()->delete();

            $cuposDisponibles = [];

            foreach ($carreras as $carrera) {
                $cuposDisponibles[$carrera->id] = (int) ($carrera->cupos ?? 0);
            }

            $posicion = 1;

            foreach ($ranking as $item) {
                $postulante = $item['postulante'];
                $promedio = $item['promedio_final'];

                $primeraOpcionId = $postulante->primera_opcion_carrera_id;
                $segundaOpcionId = $postulante->segunda_opcion_carrera_id;

                $carreraAsignadaId = null;
                $estado = 'aprobado_sin_cupo';

                if ($primeraOpcionId && ($cuposDisponibles[$primeraOpcionId] ?? 0) > 0) {
                    $carreraAsignadaId = $primeraOpcionId;
                    $estado = 'primera_opcion';
                    $cuposDisponibles[$primeraOpcionId]--;
                } elseif ($segundaOpcionId && ($cuposDisponibles[$segundaOpcionId] ?? 0) > 0) {
                    $carreraAsignadaId = $segundaOpcionId;
                    $estado = 'segunda_opcion';
                    $cuposDisponibles[$segundaOpcionId]--;
                }

                AsignacionCupo::create([
                    'postulante_id' => $postulante->id,
                    'carrera_id' => $carreraAsignadaId,
                    'fecha_asignacion' => now()->format('Y-m-d'),
                    'promedio_final' => $promedio,
                    'posicion_ranking' => $posicion,
                    'estado' => $estado,
                ]);

                $posicion++;
            }

            foreach ($reprobados as $item) {
                $postulante = $item['postulante'];

                AsignacionCupo::create([
                    'postulante_id' => $postulante->id,
                    'carrera_id' => null,
                    'fecha_asignacion' => now()->format('Y-m-d'),
                    'promedio_final' => $item['promedio_final'],
                    'posicion_ranking' => null,
                    'estado' => 'reprobado',
                ]);
            }
        });

        session()->flash('mensaje', 'Asignación de cupos generada correctamente según ranking, promedio final y cupos disponibles.');

        $this->resetPage();
    }

    public function with()
    {
        $carreras = Carrera::orderBy('nombre')->get();

        $query = AsignacionCupo::query()
            ->with([
                'postulante.user',
                'postulante.primeraOpcionCarrera',
                'postulante.segundaOpcionCarrera',
                'carrera',
            ])
            ->when($this->busqueda, function ($query) {
                $busqueda = '%' . strtolower($this->busqueda) . '%';

                $query->whereHas('postulante.user', function ($userQuery) use ($busqueda) {
                    $userQuery->whereRaw('LOWER(name) LIKE ?', [$busqueda])
                        ->orWhereRaw('LOWER(apellido) LIKE ?', [$busqueda])
                        ->orWhereRaw('LOWER(ci) LIKE ?', [$busqueda])
                        ->orWhereRaw('LOWER(username) LIKE ?', [$busqueda]);
                });
            })
            ->when($this->carreraFiltro, function ($query) {
                $query->where('carrera_id', $this->carreraFiltro);
            })
            ->when($this->estadoFiltro, function ($query) {
                $query->where('estado', $this->estadoFiltro);
            });

        $totalProcesados = (clone $query)->count();

        $totalPrimera = (clone $query)
            ->where('estado', 'primera_opcion')
            ->count();

        $totalSegunda = (clone $query)
            ->where('estado', 'segunda_opcion')
            ->count();

        $totalSinCupo = (clone $query)
            ->where('estado', 'aprobado_sin_cupo')
            ->count();

        $totalReprobados = (clone $query)
            ->where('estado', 'reprobado')
            ->count();

        $totalAceptados = $totalPrimera + $totalSegunda;

        $promedioGeneral = (clone $query)
            ->whereIn('estado', [
                'primera_opcion',
                'segunda_opcion',
                'aprobado_sin_cupo',
            ])
            ->avg('promedio_final');

        $asignaciones = $query
            ->orderByRaw('posicion_ranking IS NULL')
            ->orderBy('posicion_ranking')
            ->orderByDesc('promedio_final')
            ->paginate($this->porPagina);

        return [
            'carreras' => $carreras,
            'asignaciones' => $asignaciones,
            'totalProcesados' => $totalProcesados,
            'totalPrimera' => $totalPrimera,
            'totalSegunda' => $totalSegunda,
            'totalSinCupo' => $totalSinCupo,
            'totalReprobados' => $totalReprobados,
            'totalAceptados' => $totalAceptados,
            'promedioGeneral' => $promedioGeneral ? round($promedioGeneral, 2) : 0,
        ];
    }
};

?>

<div class="quota-container">
    <style>
        /* Variables de Sistema de Diseño */
        .quota-container {
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

        /* Rejilla de Indicadores de Admisión (7 elementos fluidos) */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .stat {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
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
        .stat-processed::before { background-color: #3b82f6; } /* Azul */
        .stat-accepted::before { background-color: #10b981; }  /* Verde */
        .stat-first::before { background-color: #6366f1; }     /* Indigo */
        .stat-second::before { background-color: #0ea5e9; }    /* Cielo */
        .stat-no-quota::before { background-color: #f59e0b; }  /* Ámbar */
        .stat-failed::before { background-color: #f43f5e; }    /* Rosa */
        .stat-average::before { background-color: #8b5cf6; }   /* Morado */

        .stat span {
            color: #64748b;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 6px;
        }
        .stat strong {
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -0.04em;
        }
        .stat-processed strong { color: #2563eb; }
        .stat-accepted strong { color: #059669; }
        .stat-first strong { color: #4f46e5; }
        .stat-second strong { color: #0369a1; }
        .stat-no-quota strong { color: #d97706; }
        .stat-failed strong { color: #e11d48; }
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

        /* Barras de Acciones e Historiales */
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

        /* Botones Premium */
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
            background: #3b82f6;
            color: #ffffff;
        }
        .btn:hover {
            background: #2563eb;
        }
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
        .badge-aprobado { background-color: #dcfce7; color: #15803d; }     /* 1ra Opción */
        .badge-pendiente { background-color: #e0f2fe; color: #0369a1; }    /* 2da Opción */
        .badge-no-quota { background-color: #fef3c7; color: #d97706; }     /* Sin Cupo */
        .badge-rechazado { background-color: #fee2e2; color: #b91c1c; }    /* Reprobado / Anulado */

        /* Indicadores y Cargas */
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
                <h2 style="font-size: 24px;">Asignación de Cupos</h2>
                <p>
                    Genere el resultado final de admisión según ranking, aprobación de materias y cupos por carrera.
                </p>
            </div>

            <div wire:loading class="loading-badge">
                Procesando datos...
            </div>
        </div>
    </div>

    {{-- Feedback Flash Mensaje --}}
    @if (session()->has('mensaje'))
        <div class="alert-message">
            {{ session('mensaje') }}
        </div>
    @endif

    {{-- Rejilla Completa de Estadísticas --}}
    <div class="grid">
        <div class="stat stat-processed">
            <span>Total procesados</span>
            strong>{{ $totalProcesados }}</strong>
        </div>

        <div class="stat stat-accepted">
            <span>Aceptados</span>
            <strong>{{ $totalAceptados }}</strong>
        </div>

        <div class="stat stat-first">
            <span>Primera opción</span>
            <strong>{{ $totalPrimera }}</strong>
        </div>

        <div class="stat stat-second">
            <span>Segunda opción</span>
            <strong>{{ $totalSegunda }}</strong>
        </div>

        <div class="stat stat-no-quota">
            <span>Aprobados sin cupo</span>
            <strong>{{ $totalSinCupo }}</strong>
        </div>

        <div class="stat stat-failed">
            <span>Reprobados</span>
            <strong>{{ $totalReprobados }}</strong>
        </div>

        <div class="stat stat-average">
            <span>Promedio aprobados</span>
            <strong>{{ number_format($promedioGeneral, 2) }}</strong>
        </div>
    </div>

    {{-- Bloque Informativo de Ejecución Automática --}}
    <div class="card" style="border-color: #cbd5e1; background: #faf5ff;">
        <h2 style="color: #4f46e5; display: inline-flex; align-items: center; gap: 8px;">
            ⚙️ Proceso automático de asignación
        </h2>

        <p style="margin-top: 8px; color: #475569; font-size: 14.5px; line-height: 1.6;">
            El sistema tomará a los postulantes aceptados, verificará si aprobaron las cuatro materias, calculará el promedio general,
            ordenará el ranking de mayor a menor y asignará cupos primero a la carrera elegida como primera opción. Si esa carrera no tiene cupos,
            intentará asignar la segunda opción. Si tampoco existe cupo, quedará como aprobado sin cupo.
        </p>

        <div class="actions-bar" style="border-top: 1px solid #e9d5ff; margin-top: 16px;">
            <div class="btn-group">
                <button
                    wire:click="generarAsignacionCupos"
                    wire:confirm="Este proceso eliminará la asignación anterior y recalculará todos los cupos. ¿Desea continuar?"
                    class="btn" style="background: #4f46e5;">
                    Ejecutar asignación de cupos
                </button>
            </div>
        </div>
    </div>

    {{-- Panel de Filtros Avanzados --}}
    <div class="card">
        <h2>Filtros de Búsqueda</h2>

        <div class="form-grid">
            <div class="form-group">
                <label>Buscar postulante</label>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="busqueda"
                    placeholder="Nombre, apellido, CI o usuario..."
                >
            </div>

            <div class="form-group">
                <label>Carrera asignada</label>
                <select wire:model.live="carreraFiltro">
                    <option value="">Todas las carreras</option>
                    @foreach ($carreras as $carrera)
                        <option value="{{ $carrera->id }}">
                            {{ $carrera->codigo_carrera ?? '' }} - {{ $carrera->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>Estado final</label>
                <select wire:model.live="estadoFiltro">
                    <option value="">Todos</option>
                    <option value="primera_opcion">Aceptado en primera opción</option>
                    <option value="segunda_opcion">Aceptado en segunda opción</option>
                    <option value="aprobado_sin_cupo">Aprobado sin cupo</option>
                    <option value="reprobado">Reprobado</option>
                    <option value="anulado">Anulado</option>
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

    {{-- Tabla de Resultados Finales --}}
    <div class="card">
        <div style="padding-bottom: 16px; border-bottom: 1px solid #f1f5f9; margin-bottom: 8px;">
            <h2 style="font-size: 18px;">Resultado Final de Admisión</h2>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th style="text-align:center; width: 100px;">Ranking</th>
                        <th>Postulante</th>
                        <th>CI</th>
                        <th>Primera opción</th>
                        <th>Segunda opción</th>
                        <th>Carrera asignada</th>
                        <th style="text-align:center; width: 100px;">Promedio</th>
                        <th style="text-align:center; width: 200px;">Estado</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($asignaciones as $asignacion)
                        <tr>
                            <td style="text-align:center;">
                                <span style="background: #f1f5f9; padding: 6px 12px; border-radius: 8px; font-weight: 700; color: #1e293b;">
                                    #{{ $asignacion->posicion_ranking ?? '-' }}
                                </span>
                            </td>

                            <td>
                                <strong style="color: #0f172a; font-size: 14.5px;">
                                    {{ $asignacion->postulante->user->name ?? '' }} {{ $asignacion->postulante->user->apellido ?? '' }}
                                </strong>
                                <span class="subtext">{{ $asignacion->postulante->user->username ?? '' }}</span>
                            </td>

                            <td>
                                <span style="font-weight: 500; color: #475569;">{{ $asignacion->postulante->user->ci ?? 'Sin CI' }}</span>
                            </td>

                            <td><span class="subtext" style="color:#475569; font-weight:500;">{{ $asignacion->postulante->primeraOpcionCarrera->nombre ?? 'Sin primera opción' }}</span></td>

                            <td><span class="subtext" style="color:#64748b;">{{ $asignacion->postulante->segundaOpcionCarrera->nombre ?? 'Sin segunda opción' }}</span></td>

                            <td>
                                @if ($asignacion->carrera)
                                    <strong style="color: #1e40af; background: #eff6ff; padding: 4px 8px; border-radius: 6px; font-size:13.5px;">
                                        {{ $asignacion->carrera->nombre }}
                                    </strong>
                                @else
                                    <span style="color:#94a3b8; font-style: italic;">Sin carrera asignada</span>
                                @endif
                            </td>

                            <td style="text-align:center;">
                                <strong style="font-size: 15px; color:#0f172a;">{{ number_format($asignacion->promedio_final, 2) }}</strong>
                            </td>

                            <td style="text-align:center;">
                                @if ($asignacion->estado === 'primera_opcion')
                                    <span class="badge badge-aprobado">Aceptado 1ra opción</span>
                                @elseif ($asignacion->estado === 'segunda_opcion')
                                    <span class="badge badge-pendiente">Aceptado 2da opción</span>
                                @elseif ($asignacion->estado === 'aprobado_sin_cupo')
                                    <span class="badge badge-no-quota">Aprobado sin cupo</span>
                                @elseif (in_array($asignacion->estado, ['reprobado', 'anulado']))
                                    <span class="badge badge-rechazado">{{ ucfirst($asignacion->estado) }}</span>
                                @else
                                    <span class="badge badge-pendiente">{{ $asignacion->estado }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align:center; padding:48px; color:#94a3b8; font-weight: 500;">
                                Todavía no se ha generado la asignación oficial de cupos para este ciclo.
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