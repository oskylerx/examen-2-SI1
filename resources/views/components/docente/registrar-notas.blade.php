<?php

use App\Models\AsignacionAcademica;
use App\Models\Calificacion;
use App\Models\Docente;
use App\Models\Examen;
use App\Models\Materia;
use App\Models\Postulante;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

new class extends Component
{
    public $gestion = '2026-1';

    public $grupo_id = '';
    public $materia_id = '';

    public $notas = [];

    public $docente = null;

    public function mount()
    {
        $this->docente = Docente::where('user_id', auth()->id())->first();

        if (! $this->docente) {
            session()->flash('mensaje', 'No se encontró el docente vinculado al usuario actual.');
        }
    }

    public function updatedGrupoId()
    {
        $this->materia_id = '';
        $this->notas = [];
    }

    public function updatedMateriaId()
    {
        $this->cargarNotas();
    }

    public function updatedGestion()
    {
        $this->grupo_id = '';
        $this->materia_id = '';
        $this->notas = [];
    }

    public function cargarNotas()
    {
        $this->notas = [];

        if (! $this->grupo_id || ! $this->materia_id) {
            return;
        }

        if (! $this->docentePuedeRegistrar()) {
            session()->flash('mensaje', 'No tiene asignada esta materia para el grupo seleccionado.');
            return;
        }

        $postulantes = $this->obtenerPostulantesDelGrupo();

        foreach ($postulantes as $postulante) {
            $examen = Examen::with('calificaciones')
                ->where('postulante_id', $postulante->id)
                ->where('materia_id', $this->materia_id)
                ->first();

            $p1 = '';
            $p2 = '';
            $ef = '';

            if ($examen) {
                $p1 = optional($examen->calificaciones->firstWhere('tipo', 'p1'))->nota;
                $p2 = optional($examen->calificaciones->firstWhere('tipo', 'p2'))->nota;
                $ef = optional($examen->calificaciones->firstWhere('tipo', 'ef'))->nota;
            }

            $this->notas[$postulante->id] = [
                'p1' => $p1,
                'p2' => $p2,
                'ef' => $ef,
            ];
        }
    }

    public function guardarNotas()
    {
        if (! $this->grupo_id || ! $this->materia_id) {
            session()->flash('mensaje', 'Debe seleccionar un grupo y una materia.');
            return;
        }

        if (! $this->docentePuedeRegistrar()) {
            session()->flash('mensaje', 'No tiene permiso para registrar notas en esta materia y grupo.');
            return;
        }

        $materia = Materia::findOrFail($this->materia_id);
        $postulantes = $this->obtenerPostulantesDelGrupo();

        if ($postulantes->isEmpty()) {
            session()->flash('mensaje', 'No existen postulantes asignados a este grupo.');
            return;
        }

        foreach ($postulantes as $postulante) {
            $p1 = $this->notas[$postulante->id]['p1'] ?? null;
            $p2 = $this->notas[$postulante->id]['p2'] ?? null;
            $ef = $this->notas[$postulante->id]['ef'] ?? null;

            if ($p1 === null || $p1 === '' || $p2 === null || $p2 === '' || $ef === null || $ef === '') {
                session()->flash('mensaje', 'Debe registrar las 3 notas de todos los postulantes.');
                return;
            }

            if (! $this->notaValida($p1) || ! $this->notaValida($p2) || ! $this->notaValida($ef)) {
                session()->flash('mensaje', 'Las notas deben estar entre 0 y 100.');
                return;
            }
        }

        DB::transaction(function () use ($postulantes, $materia) {
            foreach ($postulantes as $postulante) {
                $p1 = (float) $this->notas[$postulante->id]['p1'];
                $p2 = (float) $this->notas[$postulante->id]['p2'];
                $ef = (float) $this->notas[$postulante->id]['ef'];

                $promedio = $this->calcularPromedio($p1, $p2, $ef, $materia);

                $estado = $promedio >= ($materia->nota_min_aprob ?? 60)
                    ? 'aprobado'
                    : 'reprobado';

                $examen = Examen::updateOrCreate(
                    [
                        'materia_id' => $this->materia_id,
                        'postulante_id' => $postulante->id,
                    ],
                    [
                        'fecha_registro' => now(),
                        'promedio_final' => $promedio,
                        'estado' => $estado,
                    ]
                );

                Calificacion::updateOrCreate(
                    [
                        'examen_id' => $examen->id,
                        'tipo' => 'p1',
                    ],
                    [
                        'nota' => $p1,
                    ]
                );

                Calificacion::updateOrCreate(
                    [
                        'examen_id' => $examen->id,
                        'tipo' => 'p2',
                    ],
                    [
                        'nota' => $p2,
                    ]
                );

                Calificacion::updateOrCreate(
                    [
                        'examen_id' => $examen->id,
                        'tipo' => 'ef',
                    ],
                    [
                        'nota' => $ef,
                    ]
                );
            }
        });

        session()->flash('mensaje', 'Notas registradas correctamente.');

        $this->cargarNotas();
    }

    private function notaValida($nota)
    {
        return is_numeric($nota) && $nota >= 0 && $nota <= 100;
    }

    private function calcularPromedio($p1, $p2, $ef, $materia)
    {
        $porcentajeP1 = $materia->porcentaje_p1 ?? 30;
        $porcentajeP2 = $materia->porcentaje_p2 ?? 30;
        $porcentajeEF = $materia->porcentaje_ef ?? 40;

        $promedio = (
            ($p1 * $porcentajeP1) +
            ($p2 * $porcentajeP2) +
            ($ef * $porcentajeEF)
        ) / 100;

        return round($promedio, 2);
    }

    private function docentePuedeRegistrar()
    {
        if (! $this->docente) {
            return false;
        }

        return AsignacionAcademica::query()
            ->where('docente_id', $this->docente->id)
            ->where('grupo_id', $this->grupo_id)
            ->where('materia_id', $this->materia_id)
            ->where('gestion', $this->gestion)
            ->where('estado', 'activa')
            ->exists();
    }

    private function obtenerPostulantesDelGrupo()
    {
        return Postulante::with('user')
            ->where('grupo_id', $this->grupo_id)
            ->whereIn('estado_inscripcion', ['aceptado', 'habilitado', 'aprobado'])
            ->orderBy('id')
            ->get();
    }

    public function with()
    {
        $grupos = collect();
        $materias = collect();
        $postulantes = collect();
        $materiaSeleccionada = null;

        if ($this->docente) {
            $asignaciones = AsignacionAcademica::with(['grupo.carrera', 'materia'])
                ->where('docente_id', $this->docente->id)
                ->where('gestion', $this->gestion)
                ->where('estado', 'activa')
                ->get();

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
            }

            if ($this->grupo_id && $this->materia_id) {
                $postulantes = $this->obtenerPostulantesDelGrupo();
                $materiaSeleccionada = Materia::find($this->materia_id);
            }
        }

        return [
            'grupos' => $grupos,
            'materias' => $materias,
            'postulantes' => $postulantes,
            'materiaSeleccionada' => $materiaSeleccionada,
        ];
    }
};

?>

<div class="grades-container">
    <style>
        /* Variables de Sistema de Diseño */
        .grades-container {
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

        /* Rejilla de Formularios y Parámetros */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-top: 20px;
            margin-bottom: 4px;
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
        .form-group input:disabled, .form-group select:disabled {
            background-color: #f1f5f9;
            color: #94a3b8;
            cursor: not-allowed;
        }

        /* Panel de Indicadores de Ponderación */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
        }
        .stat span {
            color: #64748b;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 4px;
        }
        .stat strong {
            color: #4f46e5;
            font-size: 24px;
            font-weight: 800;
        }

        /* Barras de Acciones */
        .actions-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            padding-top: 20px;
            margin-top: 12px;
            border-top: 1px solid #f1f5f9;
        }
        .btn-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        /* Estilo de Botones Premium */
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
        .btn:not(.btn-secondary) {
            background: #3b82f6;
            color: #ffffff;
        }
        .btn:not(.btn-secondary):hover {
            background: #2563eb;
        }
        .btn-secondary {
            background: #f1f5f9;
            border-color: #e2e8f0;
            color: #475569;
        }
        .btn-secondary:hover {
            background: #e2e8f0;
            color: #1e293b;
        }

        /* Tablas de Calificaciones */
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
            padding: 10px 16px;
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

        /* Inputs de Notas tipo Planilla */
        .input-nota {
            width: 80px;
            padding: 8px 10px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            text-align: center;
            color: #0f172a;
            transition: all 0.15s ease;
        }
        .input-nota:focus {
            outline: none;
            border-color: #3b82f6;
            background-color: #eff6ff;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        /* Eliminar flechas nativas del input number */
        .input-nota::-webkit-outer-spin-button,
        .input-nota::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        /* Badges de Estado */
        .badge {
            padding: 5px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
            text-align: center;
        }
        .badge-aprobado { background-color: #dcfce7; color: #15803d; }
        .badge-rechazado { background-color: #fee2e2; color: #b91c1c; }
        .badge-pendiente { background-color: #f1f5f9; color: #64748b; }

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
                <h2 style="font-size: 24px;">Registrar Notas</h2>
                <p>
                    Registre las tres calificaciones por materia: Primer Parcial (P1), Segundo Parcial (P2) y Examen Final (EF).
                </p>
            </div>

            <div wire:loading class="loading-badge">
                Actualizando datos...
            </div>
        </div>
    </div>

    {{-- Feedback Flash Mensaje --}}
    @if (session()->has('mensaje'))
        <div class="alert-message">
            {{ session('mensaje') }}
        </div>
    @endif

    {{-- Selector de Parámetros --}}
    <div class="card">
        <h2>Parámetros de Evaluación</h2>

        <div class="form-grid">
            <div class="form-group">
                <label>Gestión</label>
                <input type="text" wire:model.live.debounce.300ms="gestion" placeholder="Ej: 2026-1">
            </div>

            <div class="form-group">
                <label>Grupo Académico</label>
                <select wire:model.live="grupo_id">
                    <option value="">Seleccione un grupo</option>
                    @foreach ($grupos as $grupo)
                        <option value="{{ $grupo->id }}">
                            {{ $grupo->nombre }} @if ($grupo->carrera) - {{ $grupo->carrera->nombre }} @endif
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>Materia</label>
                <select wire:model.live="materia_id" @disabled(! $grupo_id)>
                    <option value="">Seleccione una materia</option>
                    @foreach ($materias as $materia)
                        <option value="{{ $materia->id }}">{{ $materia->nombre }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- Ficha de Ponderaciones Académicas --}}
    @if ($materiaSeleccionada)
        <div class="grid">
            <div class="stat">
                <span>Porcentaje P1</span>
                <strong>{{ $materiaSeleccionada->porcentaje_p1 }}%</strong>
            </div>

            <div class="stat">
                <span>Porcentaje P2</span>
                <strong>{{ $materiaSeleccionada->porcentaje_p2 }}%</strong>
            </div>

            <div class="stat">
                <span>Porcentaje EF</span>
                <strong>{{ $materiaSeleccionada->porcentaje_ef }}%</strong>
            </div>

            <div class="stat" style="border-color: #c7d2fe; background: #faf5ff;">
                <span style="color: #4f46e5;">Nota mínima aprobación</span>
                <strong style="color: #4f46e5;">{{ $materiaSeleccionada->nota_min_aprob }}</strong>
            </div>
        </div>
    @endif

    {{-- Contenedor de Planilla de Notas --}}
    <div class="card">
        <div style="padding-bottom: 12px; border-bottom: 1px solid #f1f5f9; margin-bottom: 16px;">
            <h2 style="font-size: 18px;">Planilla de Calificaciones</h2>
        </div>

        @if (! $grupo_id || ! $materia_id)
            <div style="text-align: center; padding: 40px 20px;">
                <span style="font-size: 42px; display:block; margin-bottom: 12px;">📊</span>
                <p style="color:#94a3b8; font-weight: 500;">
                    Seleccione un grupo y una materia activa arriba para cargar la lista de postulantes.
                </p>
            </div>
        @else
            <form wire:submit.prevent="guardarNotas">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="text-align:center; width: 60px;">Nro</th>
                                <th>Postulante</th>
                                <th style="width: 140px;">CI</th>
                                <th style="text-align:center; width: 120px;">P1 ({{ $materiaSeleccionada->porcentaje_p1 ?? 0 }}%)</th>
                                <th style="text-align:center; width: 120px;">P2 ({{ $materiaSeleccionada->porcentaje_p2 ?? 0 }}%)</th>
                                <th style="text-align:center; width: 120px;">EF ({{ $materiaSeleccionada->porcentaje_ef ?? 0 }}%)</th>
                                <th style="text-align:center; width: 120px;">Promedio</th>
                                <th style="text-align:center; width: 140px;">Estado</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($postulantes as $index => $postulante)
                                @php
                                    $p1 = $notas[$postulante->id]['p1'] ?? null;
                                    $p2 = $notas[$postulante->id]['p2'] ?? null;
                                    $ef = $notas[$postulante->id]['ef'] ?? null;

                                    $promedioVista = null;
                                    $estadoVista = 'Pendiente';

                                    if ($p1 !== null && $p1 !== '' && $p2 !== null && $p2 !== '' && $ef !== null && $ef !== '' && $materiaSeleccionada) {
                                        $promedioVista = round(
                                            (
                                                ($p1 * $materiaSeleccionada->porcentaje_p1) +
                                                ($p2 * $materiaSeleccionada->porcentaje_p2) +
                                                ($ef * $materiaSeleccionada->porcentaje_ef)
                                            ) / 100,
                                            2
                                        );

                                        $estadoVista = $promedioVista >= $materiaSeleccionada->nota_min_aprob
                                            ? 'Aprobado'
                                            : 'Reprobado';
                                    }
                                @endphp

                                <tr>
                                    <td style="text-align:center; color:#94a3b8; font-weight:600;">
                                        {{ $index + 1 }}
                                    </td>

                                    <td>
                                        <strong style="color: #0f172a; font-size: 14.5px;">
                                            {{ $postulante->user->name ?? '' }} {{ $postulante->user->apellido ?? '' }}
                                        </strong>
                                    </td>

                                    <td>
                                        <span style="font-weight: 500; color: #475569;">{{ $postulante->user->ci ?? 'Sin CI' }}</span>
                                    </td>

                                    <td style="text-align:center;">
                                        <input
                                            type="number"
                                            min="0"
                                            max="100"
                                            step="0.01"
                                            wire:model.live="notas.{{ $postulante->id }}.p1"
                                            class="input-nota"
                                            placeholder="0.00"
                                        >
                                    </td>

                                    <td style="text-align:center;">
                                        <input
                                            type="number"
                                            min="0"
                                            max="100"
                                            step="0.01"
                                            wire:model.live="notas.{{ $postulante->id }}.p2"
                                            class="input-nota"
                                            placeholder="0.00"
                                        >
                                    </td>

                                    <td style="text-align:center;">
                                        <input
                                            type="number"
                                            min="0"
                                            max="100"
                                            step="0.01"
                                            wire:model.live="notas.{{ $postulante->id }}.ef"
                                            class="input-nota"
                                            placeholder="0.00"
                                        >
                                    </td>

                                    <td style="text-align:center;">
                                        @if ($promedioVista !== null)
                                            <span style="font-size: 16px; font-weight: 700; color: {{ $estadoVista === 'Aprobado' ? '#16a34a' : '#dc2626' }};">
                                                {{ number_format($promedioVista, 2) }}
                                            </span>
                                        @else
                                            <span style="color:#cbd5e1; font-weight: 500;">-</span>
                                        @endif
                                    </td>

                                    <td style="text-align:center;">
                                        @if ($estadoVista === 'Aprobado')
                                            <span class="badge badge-aprobado">Aprobado</span>
                                        @elseif ($estadoVista === 'Reprobado')
                                            <span class="badge badge-rechazado">Reprobado</span>
                                        @else
                                            <span class="badge badge-pendiente">Pendiente</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" style="text-align:center; padding:48px; color:#94a3b8; font-weight: 500;">
                                        No existen postulantes aceptados registrados en este grupo académico.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Barra Inferior de Guardado Estabilizado --}}
                @if ($postulantes->count() > 0)
                    <div class="actions-bar">
                        <div class="btn-group">
                            <button type="submit" class="btn" wire:loading.attr="disabled" wire:target="guardarNotas">
                                Guardar calificaciones
                            </button>

                            <button type="button" wire:click="cargarNotas" class="btn btn-secondary">
                                Recargar planilla
                            </button>
                        </div>

                        <div wire:loading wire:target="guardarNotas" style="font-size: 14px; color: #4f46e5; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                            <span>Compilando y guardando registros...</span>
                        </div>
                    </div>
                @endif
            </form>
        @endif
    </div>
</div>