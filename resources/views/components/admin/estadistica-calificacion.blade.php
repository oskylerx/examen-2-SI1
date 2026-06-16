<?php

use App\Models\Carrera;
use App\Models\Examen;
use App\Models\Grupo;
use App\Models\Materia;
use Livewire\Component;

new class extends Component
{
    public string $carrera_id = '';
    public string $grupo_id = '';
    public string $materia_id = '';
    public string $estado = '';
    public string $nota_min = '';
    public string $nota_max = '';
    public string $fecha_desde = '';
    public string $fecha_hasta = '';

    // Propiedades públicas obligatorias para la sincronización con JavaScript
    public int $totalEvaluaciones = 0;
    public int $totalAprobados = 0;
    public int $totalReprobados = 0;
    public float $promedioGeneral = 0;
    public float $notaMaxima = 0;
    public float $notaMinima = 0;
    public float $porcentajeAprobacion = 0;
    public array $rangos = [];

    public function updatedCarreraId()
    {
        $this->grupo_id = '';
    }

    public function limpiarFiltros()
    {
        $this->carrera_id = '';
        $this->grupo_id = '';
        $this->materia_id = '';
        $this->estado = '';
        $this->nota_min = '';
        $this->nota_max = '';
        $this->fecha_desde = '';
        $this->fecha_hasta = '';
    }

    public function imprimir()
    {
        $this->dispatch('imprimir-reporte');
    }

    private function queryExamenes()
    {
        return Examen::query()
            ->with([
                'materia',
                'postulante.user',
                'postulante.grupo.carrera',
                'calificaciones',
            ])
            ->when($this->materia_id, function ($query) {
                $query->where('materia_id', $this->materia_id);
            })
            ->when($this->estado, function ($query) {
                $query->where('estado', $this->estado);
            })
            ->when($this->nota_min !== '', function ($query) {
                $query->where('promedio_final', '>=', $this->nota_min);
            })
            ->when($this->nota_max !== '', function ($query) {
                $query->where('promedio_final', '<=', $this->nota_max);
            })
            ->when($this->fecha_desde, function ($query) {
                $query->whereDate('fecha_registro', '>=', $this->fecha_desde);
            })
            ->when($this->fecha_hasta, function ($query) {
                $query->whereDate('fecha_registro', '<=', $this->fecha_hasta);
            })
            ->when($this->carrera_id, function ($query) {
                $query->whereHas('postulante.grupo', function ($grupoQuery) {
                    $grupoQuery->where('carrera_id', $this->carrera_id);
                });
            })
            ->when($this->grupo_id, function ($query) {
                $query->whereHas('postulante', function ($postulanteQuery) {
                    $postulanteQuery->where('grupo_id', $this->grupo_id);
                });
            });
    }

    private function estadisticasPorMateria($examenes)
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
                    'total' => $total,
                    'aprobados' => $aprobados,
                    'reprobados' => $reprobados,
                    'promedio' => round($items->avg('promedio_final'), 2),
                    'nota_maxima' => round($items->max('promedio_final'), 2),
                    'nota_minima' => round($items->min('promedio_final'), 2),
                    'porcentaje_aprobacion' => $total > 0 ? round(($aprobados / $total) * 100, 2) : 0,
                ];
            })
            ->sortBy('materia')
            ->values();
    }

    private function estadisticasPorGrupo($examenes)
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
                    'carrera' => $grupo->carrera->nombre ?? 'Sin carrera',
                    'total' => $total,
                    'aprobados' => $aprobados,
                    'reprobados' => $reprobados,
                    'promedio' => round($items->avg('promedio_final'), 2),
                    'porcentaje_aprobacion' => $total > 0 ? round(($aprobados / $total) * 100, 2) : 0,
                ];
            })
            ->sortBy('grupo')
            ->values();
    }

    private function distribucionRangos($examenes)
    {
        $rangosConfig = [
            '0 - 50' => [0, 50],
            '51 - 59' => [51, 59],
            '60 - 70' => [60, 70],
            '71 - 80' => [71, 80],
            '81 - 90' => [81, 90],
            '91 - 100' => [91, 100],
        ];

        return collect($rangosConfig)->map(function ($rango, $nombre) use ($examenes) {
            [$min, $max] = $rango;
            $cantidad = $examenes
                ->filter(fn ($examen) => $examen->promedio_final >= $min && $examen->promedio_final <= $max)
                ->count();

            return [
                'rango' => $nombre,
                'cantidad' => $cantidad,
            ];
        })->values();
    }

    public function with()
    {
        $examenes = $this->queryExamenes()
            ->orderByDesc('promedio_final')
            ->get();

        // 1. Sincronizamos las propiedades del componente ($this->)
        $this->totalEvaluaciones = $examenes->count();
        $this->totalAprobados = $examenes->where('estado', 'aprobado')->count();
        $this->totalReprobados = $examenes->where('estado', 'reprobado')->count();

        $this->promedioGeneral = $this->totalEvaluaciones > 0
            ? round($examenes->avg('promedio_final'), 2)
            : 0;

        $this->notaMaxima = $this->totalEvaluaciones > 0
            ? round($examenes->max('promedio_final'), 2)
            : 0;

        $this->notaMinima = $this->totalEvaluaciones > 0
            ? round($examenes->min('promedio_final'), 2)
            : 0;

        $this->porcentajeAprobacion = $this->totalEvaluaciones > 0
            ? round(($this->totalAprobados / $this->totalEvaluaciones) * 100, 2)
            : 0;

        $this->rangos = $this->distribucionRangos($examenes)->toArray();

        $grupos = Grupo::with('carrera')
            ->when($this->carrera_id, function ($query) {
                $query->where('carrera_id', $this->carrera_id);
            })
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        // 2. Retornamos explícitamente al arreglo para forzar el renderizado en Blade
        return [
            'carreras' => Carrera::orderBy('nombre')->get(),
            'grupos' => $grupos,
            'materias' => Materia::where('activo', true)->orderBy('nombre')->get(),
            'totalEvaluaciones' => $this->totalEvaluaciones,
            'totalAprobados' => $this->totalAprobados,
            'totalReprobados' => $this->totalReprobados,
            'promedioGeneral' => $this->promedioGeneral,
            'notaMaxima' => $this->notaMaxima,
            'notaMinima' => $this->notaMinima,
            'porcentajeAprobacion' => $this->porcentajeAprobacion,
            'porMateria' => $this->estadisticasPorMateria($examenes),
            'porGrupo' => $this->estadisticasPorGrupo($examenes),
        ];
    }
};

?>

<div class="stats-dashboard-container">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        /* Variables de Sistema de Diseño Premium */
        .stats-dashboard-container {
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

        h2 {
            color: #0f172a;
            font-size: 18px;
            font-weight: 700;
            margin: 0 0 14px 0;
            letter-spacing: -0.025em;
        }
        p {
            color: #64748b;
            font-size: 14px;
            margin: 0;
            line-height: 1.5;
        }

        /* Panel de Indicadores de Control Lateral */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
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
            background-color: #cbd5e1;
        }
        .stat-total::before { background-color: #3b82f6; }
        .stat-approved::before { background-color: #10b981; }
        .stat-failed::before { background-color: #ef4444; }
        .stat-average::before { background-color: #8b5cf6; }
        .stat-max::before { background-color: #6366f1; }
        .stat-min::before { background-color: #0ea5e9; }
        .stat-percent::before { background-color: #f59e0b; }

        .stat span {
            color: #64748b;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 4px;
        }
        .stat strong {
            font-size: 24px;
            font-weight: 800;
            color: #0f172a;
        }

        /* Área de Secciones de Gráficos */
        .charts-main-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 24px;
            margin-bottom: 24px;
        }
        @media (max-width: 992px) {
            .charts-main-grid { grid-template-columns: 1fr; }
        }
        .chart-container-box {
            position: relative;
            height: 260px;
            width: 100%;
        }

        /* Rejilla de Formularios y Filtros */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-top: 16px;
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
            margin-top: 16px;
        }
        .btn-group { display: flex; gap: 10px; flex-wrap: wrap; }

        /* Botones */
        .btn {
            padding: 10px 18px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 1px solid transparent;
        }
        .btn-secondary { background: #f1f5f9; border-color: #e2e8f0; color: #475569; }
        .btn-secondary:hover { background: #e2e8f0; color: #1e293b; }
        .btn-danger-text { background: transparent; color: #ef4444; }
        .btn-danger-text:hover { background: #fef2f2; }

        /* Tablas Avanzadas */
        .table-responsive {
            overflow-x: auto;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            margin-top: 12px;
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
        }
        .table tr:last-child td { border-bottom: none; }
        .table tr:hover td { background-color: #f8fafc; }

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

        @media print {
            .no-print, .card:has(.form-grid) { display: none !important; }
            .stats-dashboard-container { background: white; padding: 0; }
            .card { box-shadow: none; border: 1px solid #cbd5e1; page-break-inside: avoid; }
        }
    </style>

    {{-- Encabezado del Módulo --}}
    <div class="card no-print">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
            <div>
                <h2 style="font-size: 24px; margin: 0;">CU12 - Resumen Estadístico de Calificaciones</h2>
                <p style="margin-top:4px;">
                    Genere estadísticas por materia, carrera, grupo, periodo y rango de notas con visualización gráfica.
                </p>
            </div>

            <div wire:loading class="loading-badge">
                Procesando estadísticas...
            </div>
        </div>
    </div>

    {{-- Parámetros de Filtrado --}}
    <div class="card no-print">
        <h2>Parámetros del resumen</h2>

        <div class="form-grid">
            <div class="form-group">
                <label>Carrera</label>
                <select wire:model.live="carrera_id">
                    <option value="">Todas las carreras</option>
                    @foreach ($carreras as $carrera)
                        <option value="{{ $carrera->id }}">{{ $carrera->codigo_carrera ?? '' }} - {{ $carrera->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>Grupo</label>
                <select wire:model.live="grupo_id">
                    <option value="">Todos los grupos</option>
                    @foreach ($grupos as $grupo)
                        <option value="{{ $grupo->id }}">{{ $grupo->nombre }}</option>
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
                <label>Resultado</label>
                <select wire:model.live="estado">
                    <option value="">Todos</option>
                    <option value="aprobado">Aprobados</option>
                    <option value="reprobado">Reprobados</option>
                </select>
            </div>

            <div class="form-group">
                <label>Nota mínima</label>
                <input type="number" min="0" max="100" step="0.01" wire:model.live.debounce.300ms="nota_min">
            </div>

            <div class="form-group">
                <label>Nota máxima</label>
                <input type="number" min="0" max="100" step="0.01" wire:model.live.debounce.300ms="nota_max">
            </div>

            <div class="form-group">
                <label>Desde</label>
                <input type="date" wire:model.live="fecha_desde">
            </div>

            <div class="form-group">
                <label>Hasta</label>
                <input type="date" wire:model.live="fecha_hasta">
            </div>
        </div>

        <div class="actions-bar">
            <div class="btn-group">
                <button wire:click="limpiarFiltros" class="btn btn-danger-text" style="font-weight:600;">
                    Limpiar filtros
                </button>

                <button wire:click="imprimir" class="btn btn-secondary">
                    Imprimir resumen
                </button>
            </div>
        </div>
    </div>

    {{-- ÁREA EVALUADA (Se imprime) --}}
    <div id="area-imprimir">
        
        {{-- Bloques de Estadísticas Rápidas --}}
        <div class="grid">
            <div class="stat stat-total">
                <span>Total evaluaciones</span>
                <strong>{{ $totalEvaluaciones }}</strong>
            </div>
            <div class="stat stat-approved">
                <span>Aprobados</span>
                <strong style="color:#059669;">{{ $totalAprobados }}</strong>
            </div>
            <div class="stat stat-failed">
                <span>Reprobados</span>
                <strong style="color:#dc2626;">{{ $totalReprobados }}</strong>
            </div>
            <div class="stat stat-average">
                <span>Promedio general</span>
                <strong style="color:#7c3aed;">{{ number_format($promedioGeneral, 2) }}</strong>
            </div>
            <div class="stat stat-max">
                <span>Nota máxima</span>
                <strong style="color:#4f46e5;">{{ $notaMaxima }}</strong>
            </div>
            <div class="stat stat-min">
                <span>Nota mínima</span>
                <strong style="color:#0284c7;">{{ $notaMinima }}</strong>
            </div>
            <div class="stat stat-percent">
                <span>% Aprobación</span>
                <strong style="color:#d97706;">{{ $porcentajeAprobacion }}%</strong>
            </div>
        </div>

        {{-- SECCIÓN: GRÁFICOS REACTIVOS --}}
        <div class="charts-main-grid">
            
            <div class="card" style="margin-bottom:0;" 
                 x-data="{
                    chart: null,
                    init() {
                        this.chart = new Chart(this.$refs.canvas, {
                            type: 'doughnut',
                            data: {
                                labels: ['Aprobados', 'Reprobados'],
                                datasets: [{ data: [$wire.totalAprobados, $wire.totalReprobados], backgroundColor: ['#10b981', '#ef4444'], borderWidth: 2 }]
                            },
                            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
                        });
                        this.$watch('$wire.totalAprobados', () => this.updateChart());
                        this.$watch('$wire.totalReprobados', () => this.updateChart());
                    },
                    updateChart() {
                        this.chart.data.datasets[0].data = [$wire.totalAprobados, $wire.totalReprobados];
                        this.chart.update();
                    }
                 }" wire:ignore>
                <h2>Rendimiento General</h2>
                <div class="chart-container-box">
                    <canvas x-ref="canvas"></canvas>
                </div>
            </div>

            <div class="card" style="margin-bottom:0;"
                 x-data="{
                    chart: null,
                    init() {
                        let datos = $wire.rangos || [];
                        this.chart = new Chart(this.$refs.canvas, {
                            type: 'bar',
                            data: {
                                labels: datos.map(r => r.rango),
                                datasets: [{ label: 'Cantidad', data: datos.map(r => r.cantidad), backgroundColor: '#6366f1', borderRadius: 6 }]
                            },
                            options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
                        });
                        this.$watch('$wire.rangos', (val) => {
                            this.chart.data.labels = val.map(r => r.rango);
                            this.chart.data.datasets[0].data = val.map(r => r.cantidad);
                            this.chart.update();
                        });
                    }
                 }" wire:ignore>
                <h2>Distribución por Rangos de Notas</h2>
                <div class="chart-container-box">
                    <canvas x-ref="canvas"></canvas>
                </div>
            </div>

        </div>

        {{-- Tablas de Datos de Resumen --}}
        <div class="card">
            <h2>Resumen por Materia</h2>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Materia</th>
                            <th style="text-align:center;">Evaluaciones</th>
                            <th style="text-align:center;">Aprobados</th>
                            <th style="text-align:center;">Reprobados</th>
                            <th style="text-align:center;">Promedio</th>
                            <th style="text-align:center;">Máxima</th>
                            <th style="text-align:center;">Mínima</th>
                            <th style="text-align:center;">% Aprobación</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($porMateria as $fila)
                            <tr>
                                <td><strong style="color:#1e293b;">{{ $fila['materia'] }}</strong></td>
                                <td style="text-align:center;">{{ $fila['total'] }}</td>
                                <td style="text-align:center; color:#16a34a; font-weight:600;">{{ $fila['aprobados'] }}</td>
                                <td style="text-align:center; color:#dc2626; font-weight:600;">{{ $fila['reprobados'] }}</td>
                                <td style="text-align:center;"><strong>{{ number_format($fila['promedio'], 2) }}</strong></td>
                                <td style="text-align:center;">{{ $fila['nota_maxima'] }}</td>
                                <td style="text-align:center;">{{ $fila['nota_minima'] }}</td>
                                <td style="text-align:center; font-weight:600;">{{ $fila['porcentaje_aprobacion'] }}%</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="text-align:center; padding:32px; color:#94a3b8;">
                                    No existen datos para los filtros seleccionados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <h2>Resumen por Grupo</h2>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Grupo</th>
                            <th>Carrera</th>
                            <th style="text-align:center;">Evaluaciones</th>
                            <th style="text-align:center;">Aprobados</th>
                            <th style="text-align:center;">Reprobados</th>
                            <th style="text-align:center;">Promedio</th>
                            <th style="text-align:center;">% Aprobación</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($porGrupo as $fila)
                            <tr>
                                <td><strong style="color:#0f172a;">{{ $fila['grupo'] }}</strong></td>
                                <td><span style="color:#475569; font-weight:500;">{{ $fila['carrera'] }}</span></td>
                                <td style="text-align:center;">{{ $fila['total'] }}</td>
                                <td style="text-align:center; color:#16a34a;">{{ $fila['aprobados'] }}</td>
                                <td style="text-align:center; color:#dc2626;">{{ $fila['reprobados'] }}</td>
                                <td style="text-align:center;"><strong>{{ number_format($fila['promedio'], 2) }}</strong></td>
                                <td style="text-align:center; font-weight:600;">{{ $fila['porcentaje_aprobacion'] }}%</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="text-align:center; padding:32px; color:#94a3b8;">
                                    No existen datos por grupo.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @script
    <script>
        $wire.on('imprimir-reporte', () => {
            window.print();
        });
    </script>
    @endscript
</div>