<?php

use App\Models\AsignacionAcademica;
use App\Models\Docente;
use App\Models\Grupo;
use App\Models\Materia;
use App\Models\Aula;
use App\Models\Horario;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public $busqueda = '';
    public $grupoFiltro = '';
    public $docenteFiltro = '';
    public $materiaFiltro = '';
    public $estadoFiltro = '';
    public $gestionFiltro = '2026-1';
    public $porPagina = 10;

    public $modal = false;
    public $asignacionId = null;

    public $horario_id = '';
    public $aula_id = '';
    public $grupo_id = '';
    public $docente_id = '';
    public $materia_id = '';
    public $gestion = '2026-1';
    public $fecha_asignacion = '';
    public $estado = 'activa';

    public function updated($property)
    {
        if (in_array($property, ['busqueda', 'grupoFiltro', 'docenteFiltro', 'materiaFiltro', 'estadoFiltro', 'gestionFiltro', 'porPagina'])) {
            $this->resetPage();
        }
    }

    public function rules()
    {
        return [
            'horario_id' => ['required', Rule::exists(new Horario()->getTable(), 'id')],
            'aula_id' => ['required', Rule::exists(new Aula()->getTable(), 'id')],
            'grupo_id' => ['required', Rule::exists(new Grupo()->getTable(), 'id')],
            'docente_id' => ['required', Rule::exists(new Docente()->getTable(), 'id')],
            'materia_id' => ['required', Rule::exists(new Materia()->getTable(), 'id')],
            'gestion' => ['required', 'string', 'max:50'],
            'fecha_asignacion' => ['nullable', 'date'],
            'estado' => ['required', Rule::in(['activa', 'inactiva', 'finalizada'])],
        ];
    }

    public function limpiarFiltros()
    {
        $this->busqueda = '';
        $this->grupoFiltro = '';
        $this->docenteFiltro = '';
        $this->materiaFiltro = '';
        $this->estadoFiltro = '';
        $this->gestionFiltro = '2026-1';
        $this->porPagina = 10;

        $this->resetPage();
    }

    public function abrirCrear()
    {
        $this->resetValidation();

        $this->asignacionId = null;
        $this->horario_id = '';
        $this->aula_id = '';
        $this->grupo_id = '';
        $this->docente_id = '';
        $this->materia_id = '';
        $this->gestion = $this->gestionFiltro ?: '2026-1';
        $this->fecha_asignacion = now()->format('Y-m-d\TH:i');
        $this->estado = 'activa';

        $this->modal = true;
    }

    public function editar($id)
    {
        $asignacion = AsignacionAcademica::findOrFail($id);

        $this->resetValidation();

        $this->asignacionId = $asignacion->id;
        $this->horario_id = $asignacion->horario_id;
        $this->aula_id = $asignacion->aula_id;
        $this->grupo_id = $asignacion->grupo_id;
        $this->docente_id = $asignacion->docente_id;
        $this->materia_id = $asignacion->materia_id;
        $this->gestion = $asignacion->gestion;
        $this->fecha_asignacion = $asignacion->fecha_asignacion ? Carbon::parse($asignacion->fecha_asignacion)->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i');
        $this->estado = $asignacion->estado ?? 'activa';

        $this->modal = true;
    }

    // public function guardar()
    // {
    //     $data = $this->validate();

    //     $data['fecha_asignacion'] = $this->fecha_asignacion
    //         ? Carbon::parse($this->fecha_asignacion)
    //         : now();

    //     if ($this->existeMateriaEnGrupo()) {
    //         session()->flash('mensaje', 'Este grupo ya tiene asignada esa materia en la gestión seleccionada.');
    //         return;
    //     }

    //     if ($this->docenteTieneChoqueHorario()) {
    //         session()->flash('mensaje', 'El docente ya tiene una asignación en ese horario.');
    //         return;
    //     }

    //     if ($this->aulaTieneChoqueHorario()) {
    //         session()->flash('mensaje', 'El aula ya está ocupada en ese horario.');
    //         return;
    //     }

    //     if ($this->docenteSuperaCuatroGrupos()) {
    //         session()->flash('mensaje', 'El docente no puede tener más de 4 grupos asignados.');
    //         return;
    //     }

    //     if ($this->asignacionId) {
    //         AsignacionAcademica::findOrFail($this->asignacionId)->update($data);
    //         session()->flash('mensaje', 'Asignación académica actualizada correctamente.');
    //     } else {
    //         AsignacionAcademica::create($data);
    //         session()->flash('mensaje', 'Asignación académica registrada correctamente.');
    //     }

    //     $this->cerrarModal();
    //     $this->resetPage();
    // }

    public function guardar()
    {
        $data = $this->validate();

        $data['fecha_asignacion'] = $this->fecha_asignacion ? Carbon::parse($this->fecha_asignacion) : now();

        // 1. Un grupo no puede repetir la misma materia en la misma gestión
        if ($this->grupoYaTieneMateria()) {
            session()->flash('mensaje', 'Este grupo ya tiene asignada esta materia en la gestión seleccionada.');
            return;
        }

        // 2. Un grupo no puede tener dos clases en el mismo horario
        if ($this->grupoTieneChoqueHorario()) {
            session()->flash('mensaje', 'Este grupo ya tiene una clase asignada en ese horario.');
            return;
        }

        // 3. Un docente no puede estar en dos grupos en el mismo horario
        if ($this->docenteTieneChoqueHorario()) {
            session()->flash('mensaje', 'El docente ya está asignado a otro grupo en ese mismo horario.');
            return;
        }

        // 4. Un aula no puede ser usada por dos grupos en el mismo horario
        if ($this->aulaTieneChoqueHorario()) {
            session()->flash('mensaje', 'El aula ya está ocupada por otro grupo en ese mismo horario.');
            return;
        }

        // 5. Un docente no puede tener más de 4 grupos diferentes
        if ($this->docenteSuperaCuatroGrupos()) {
            session()->flash('mensaje', 'El docente no puede ser asignado a más de 4 grupos en la misma gestión.');
            return;
        }

        // 6. Un grupo no debe tener más de 4 materias activas
        if ($this->grupoSuperaCuatroMaterias()) {
            session()->flash('mensaje', 'Este grupo ya tiene sus 4 materias asignadas.');
            return;
        }

        if ($this->asignacionId) {
            AsignacionAcademica::findOrFail($this->asignacionId)->update($data);
            session()->flash('mensaje', 'Asignación académica actualizada correctamente.');
        } else {
            AsignacionAcademica::create($data);
            session()->flash('mensaje', 'Asignación académica registrada correctamente.');
        }

        $this->cerrarModal();
        $this->resetPage();
    }

    // private function existeMateriaEnGrupo()
    // {
    //     return AsignacionAcademica::query()
    //         ->where('grupo_id', $this->grupo_id)
    //         ->where('materia_id', $this->materia_id)
    //         ->where('gestion', $this->gestion)
    //         ->when($this->asignacionId, function ($query) {
    //             $query->where('id', '!=', $this->asignacionId);
    //         })
    //         ->exists();
    // }

    // private function docenteTieneChoqueHorario()
    // {
    //     return AsignacionAcademica::query()
    //         ->where('docente_id', $this->docente_id)
    //         ->where('horario_id', $this->horario_id)
    //         ->where('gestion', $this->gestion)
    //         ->where('estado', 'activa')
    //         ->when($this->asignacionId, function ($query) {
    //             $query->where('id', '!=', $this->asignacionId);
    //         })
    //         ->exists();
    // }

    // private function aulaTieneChoqueHorario()
    // {
    //     return AsignacionAcademica::query()
    //         ->where('aula_id', $this->aula_id)
    //         ->where('horario_id', $this->horario_id)
    //         ->where('gestion', $this->gestion)
    //         ->where('estado', 'activa')
    //         ->when($this->asignacionId, function ($query) {
    //             $query->where('id', '!=', $this->asignacionId);
    //         })
    //         ->exists();
    // }

    // private function docenteSuperaCuatroGrupos()
    // {
    //     $gruposActuales = AsignacionAcademica::query()
    //         ->where('docente_id', $this->docente_id)
    //         ->where('gestion', $this->gestion)
    //         ->where('estado', 'activa')
    //         ->when($this->asignacionId, function ($query) {
    //             $query->where('id', '!=', $this->asignacionId);
    //         })
    //         ->distinct()
    //         ->pluck('grupo_id')
    //         ->toArray();

    //     $gruposActuales[] = (int) $this->grupo_id;

    //     return count(array_unique($gruposActuales)) > 4;
    // }

    private function grupoYaTieneMateria()
    {
        return AsignacionAcademica::query()
            ->where('grupo_id', $this->grupo_id)
            ->where('materia_id', $this->materia_id)
            ->where('gestion', $this->gestion)
            ->where('estado', 'activa')
            ->when($this->asignacionId, function ($query) {
                $query->where('id', '!=', $this->asignacionId);
            })
            ->exists();
    }

    private function grupoTieneChoqueHorario()
    {
        return AsignacionAcademica::query()
            ->where('grupo_id', $this->grupo_id)
            ->where('horario_id', $this->horario_id)
            ->where('gestion', $this->gestion)
            ->where('estado', 'activa')
            ->when($this->asignacionId, function ($query) {
                $query->where('id', '!=', $this->asignacionId);
            })
            ->exists();
    }

    private function docenteTieneChoqueHorario()
    {
        return AsignacionAcademica::query()
            ->where('docente_id', $this->docente_id)
            ->where('horario_id', $this->horario_id)
            ->where('gestion', $this->gestion)
            ->where('estado', 'activa')
            ->when($this->asignacionId, function ($query) {
                $query->where('id', '!=', $this->asignacionId);
            })
            ->exists();
    }

    private function aulaTieneChoqueHorario()
    {
        return AsignacionAcademica::query()
            ->where('aula_id', $this->aula_id)
            ->where('horario_id', $this->horario_id)
            ->where('gestion', $this->gestion)
            ->where('estado', 'activa')
            ->when($this->asignacionId, function ($query) {
                $query->where('id', '!=', $this->asignacionId);
            })
            ->exists();
    }

    private function docenteSuperaCuatroGrupos()
    {
        $gruposActuales = AsignacionAcademica::query()
            ->where('docente_id', $this->docente_id)
            ->where('gestion', $this->gestion)
            ->where('estado', 'activa')
            ->when($this->asignacionId, function ($query) {
                $query->where('id', '!=', $this->asignacionId);
            })
            ->pluck('grupo_id')
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->toArray();

        $gruposActuales[] = (int) $this->grupo_id;

        return count(array_unique($gruposActuales)) > 4;
    }

    private function grupoSuperaCuatroMaterias()
    {
        $materiasActuales = AsignacionAcademica::query()
            ->where('grupo_id', $this->grupo_id)
            ->where('gestion', $this->gestion)
            ->where('estado', 'activa')
            ->when($this->asignacionId, function ($query) {
                $query->where('id', '!=', $this->asignacionId);
            })
            ->pluck('materia_id')
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->toArray();

        $materiasActuales[] = (int) $this->materia_id;

        return count(array_unique($materiasActuales)) > 4;
    }

    public function generarAsignacionesAutomaticas()
    {
        $gestion = $this->gestionFiltro ?: '2026-1';

        $materias = Materia::where('activo', true)->orderBy('id')->get();

        $grupos = Grupo::where('activo', true)->orderBy('id')->get();

        $docentes = Docente::orderBy('id')->get();

        $aulas = Aula::where('activo', true)->orderBy('id')->get();

        $horarios = Horario::where('activo', true)->orderBy('dia')->orderBy('turno')->orderBy('hora_inicio')->get();

        if ($materias->count() < 4) {
            session()->flash('mensaje', 'Debe tener registradas las 4 materias activas: Matemáticas, Física, Inglés y Computación.');
            return;
        }

        if ($grupos->isEmpty()) {
            session()->flash('mensaje', 'No existen grupos activos para generar asignaciones.');
            return;
        }

        if ($docentes->isEmpty()) {
            session()->flash('mensaje', 'No existen docentes registrados para asignar.');
            return;
        }

        if ($aulas->isEmpty()) {
            session()->flash('mensaje', 'No existen aulas activas para asignar.');
            return;
        }

        if ($horarios->isEmpty()) {
            session()->flash('mensaje', 'No existen horarios activos para asignar.');
            return;
        }

        $creadas = 0;
        $yaExistian = 0;
        $noAsignadas = [];

        foreach ($grupos as $grupo) {
            foreach ($materias as $materia) {
                if ($this->existeMateriaGrupoAutomatico($grupo->id, $materia->id, $gestion)) {
                    $yaExistian++;
                    continue;
                }

                if ($this->grupoTieneCuatroMateriasAutomatico($grupo->id, $gestion)) {
                    break;
                }

                $espacio = $this->buscarEspacioDisponibleAutomatico($grupo->id, $materia->id, $gestion, $docentes, $aulas, $horarios);

                if (!$espacio) {
                    $noAsignadas[] = "{$grupo->nombre} - {$materia->nombre}";
                    continue;
                }

                AsignacionAcademica::create([
                    'horario_id' => $espacio['horario_id'],
                    'aula_id' => $espacio['aula_id'],
                    'grupo_id' => $grupo->id,
                    'docente_id' => $espacio['docente_id'],
                    'materia_id' => $materia->id,
                    'gestion' => $gestion,
                    'fecha_asignacion' => now(),
                    'estado' => 'activa',
                ]);

                $creadas++;
            }
        }

        $mensaje = "Asignación automática finalizada. Creadas: {$creadas}. Ya existentes: {$yaExistian}.";

        if (count($noAsignadas) > 0) {
            $mensaje .= ' No se pudieron asignar: ' . implode(', ', array_slice($noAsignadas, 0, 5));

            if (count($noAsignadas) > 5) {
                $mensaje .= ' y ' . (count($noAsignadas) - 5) . ' más.';
            }
        }

        session()->flash('mensaje', $mensaje);

        $this->resetPage();
    }

    private function buscarEspacioDisponibleAutomatico($grupoId, $materiaId, $gestion, $docentes, $aulas, $horarios)
    {
        $docentesOrdenados = $docentes->sortBy(function ($docente) use ($gestion) {
            return $this->cantidadGruposDocenteAutomatico($docente->id, $gestion);
        });

        foreach ($horarios as $horario) {
            if ($this->grupoHorarioOcupadoAutomatico($grupoId, $horario->id, $gestion)) {
                continue;
            }

            foreach ($aulas as $aula) {
                if ($this->aulaHorarioOcupadoAutomatico($aula->id, $horario->id, $gestion)) {
                    continue;
                }

                foreach ($docentesOrdenados as $docente) {
                    if ($this->docenteHorarioOcupadoAutomatico($docente->id, $horario->id, $gestion)) {
                        continue;
                    }

                    if (!$this->docentePuedeTomarGrupoAutomatico($docente->id, $grupoId, $gestion)) {
                        continue;
                    }

                    return [
                        'horario_id' => $horario->id,
                        'aula_id' => $aula->id,
                        'docente_id' => $docente->id,
                    ];
                }
            }
        }

        return null;
    }

    private function existeMateriaGrupoAutomatico($grupoId, $materiaId, $gestion)
    {
        return AsignacionAcademica::query()->where('grupo_id', $grupoId)->where('materia_id', $materiaId)->where('gestion', $gestion)->where('estado', 'activa')->exists();
    }

    private function grupoTieneCuatroMateriasAutomatico($grupoId, $gestion)
    {
        $totalMaterias = AsignacionAcademica::query()->where('grupo_id', $grupoId)->where('gestion', $gestion)->where('estado', 'activa')->distinct()->count('materia_id');

        return $totalMaterias >= 4;
    }

    private function grupoHorarioOcupadoAutomatico($grupoId, $horarioId, $gestion)
    {
        return AsignacionAcademica::query()->where('grupo_id', $grupoId)->where('horario_id', $horarioId)->where('gestion', $gestion)->where('estado', 'activa')->exists();
    }

    private function aulaHorarioOcupadoAutomatico($aulaId, $horarioId, $gestion)
    {
        return AsignacionAcademica::query()->where('aula_id', $aulaId)->where('horario_id', $horarioId)->where('gestion', $gestion)->where('estado', 'activa')->exists();
    }

    private function docenteHorarioOcupadoAutomatico($docenteId, $horarioId, $gestion)
    {
        return AsignacionAcademica::query()->where('docente_id', $docenteId)->where('horario_id', $horarioId)->where('gestion', $gestion)->where('estado', 'activa')->exists();
    }

    private function docentePuedeTomarGrupoAutomatico($docenteId, $grupoId, $gestion)
    {
        $yaTieneEsteGrupo = AsignacionAcademica::query()->where('docente_id', $docenteId)->where('grupo_id', $grupoId)->where('gestion', $gestion)->where('estado', 'activa')->exists();

        if ($yaTieneEsteGrupo) {
            return true;
        }

        $cantidadGrupos = $this->cantidadGruposDocenteAutomatico($docenteId, $gestion);

        return $cantidadGrupos < 4;
    }

    private function cantidadGruposDocenteAutomatico($docenteId, $gestion)
    {
        return AsignacionAcademica::query()->where('docente_id', $docenteId)->where('gestion', $gestion)->where('estado', 'activa')->distinct()->count('grupo_id');
    }

    public function eliminar($id)
    {
        AsignacionAcademica::findOrFail($id)->delete();

        session()->flash('mensaje', 'Asignación académica eliminada correctamente.');
        $this->resetPage();
    }

    public function cambiarEstado($id)
    {
        $asignacion = AsignacionAcademica::findOrFail($id);

        $nuevoEstado = $asignacion->estado === 'activa' ? 'inactiva' : 'activa';

        $asignacion->update([
            'estado' => $nuevoEstado,
        ]);

        session()->flash('mensaje', 'Estado de la asignación actualizado correctamente.');
    }

    public function cerrarModal()
    {
        $this->modal = false;
        $this->resetValidation();
    }

    public function with()
    {
        $docentes = Docente::with('user')->orderBy('id')->get();

        $grupos = Grupo::with('carrera')->where('activo', true)->orderBy('nombre')->get();

        $materias = Materia::where('activo', true)->orderBy('nombre')->get();

        $aulas = Aula::where('activo', true)->orderBy('nombre')->get();

        $horarios = Horario::where('activo', true)->orderBy('dia')->orderBy('turno')->orderBy('hora_inicio')->get();

        $asignaciones = AsignacionAcademica::query()
            ->with(['horario', 'aula', 'grupo.carrera', 'docente.user', 'materia'])
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
                        })
                        ->orWhereHas('docente.user', function ($userQuery) use ($busqueda) {
                            $userQuery->whereRaw('LOWER(name) LIKE ?', [$busqueda])->orWhereRaw('LOWER(apellido) LIKE ?', [$busqueda]);
                        });
                });
            })
            ->when($this->grupoFiltro, function ($query) {
                $query->where('grupo_id', $this->grupoFiltro);
            })
            ->when($this->docenteFiltro, function ($query) {
                $query->where('docente_id', $this->docenteFiltro);
            })
            ->when($this->materiaFiltro, function ($query) {
                $query->where('materia_id', $this->materiaFiltro);
            })
            ->when($this->estadoFiltro, function ($query) {
                $query->where('estado', $this->estadoFiltro);
            })
            ->when($this->gestionFiltro, function ($query) {
                $query->where('gestion', $this->gestionFiltro);
            })
            ->orderByDesc('fecha_asignacion')
            ->paginate($this->porPagina);

        return [
            'docentes' => $docentes,
            'grupos' => $grupos,
            'materias' => $materias,
            'aulas' => $aulas,
            'horarios' => $horarios,
            'asignaciones' => $asignaciones,
        ];
    }
};

?>
<div class="assign-container">
    <style>
        /* Variables de Sistema de Diseño */
        .assign-container {
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

        .form-group input,
        .form-group select {
            padding: 10px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
            color: #334155;
            background-color: #ffffff;
            transition: all 0.2s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
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

        .btn:not(.btn-secondary):not(.btn-danger-text) {
            background: #3b82f6;
            color: #ffffff;
        }

        .btn:not(.btn-secondary):not(.btn-danger-text):hover {
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

        .btn-danger-text {
            background: transparent;
            color: #ef4444;
            padding: 10px 14px;
        }

        .btn-danger-text:hover {
            background: #fef2f2;
            border-radius: 8px;
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

        /* Subtextos descriptivos dentro de celdas */
        .subtext {
            color: #64748b;
            font-size: 12px;
            display: block;
            margin-top: 2px;
        }

        /* Badges de Estado */
        .badge {
            padding: 6px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
            text-transform: capitalize;
        }

        .badge-aprobado {
            background-color: #dcfce7;
            color: #15803d;
        }

        /* Activa */
        .badge-pendiente {
            background-color: #eff6ff;
            color: #1e40af;
        }

        /* Finalizada */
        .badge-rechazado {
            background-color: #fee2e2;
            color: #b91c1c;
        }

        /* Inactiva */

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

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: .6;
            }
        }
    </style>

    {{-- Cabecera de Módulo --}}
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div>
                <h2 style="font-size: 24px;">Asignación Académica</h2>
                <p>
                    Asigne docentes a grupos, materias, aulas y horarios para organizar el CUP.
                </p>
            </div>

            <div wire:loading class="loading-badge">
                Actualizando datos...
            </div>
        </div>
    </div>

    {{-- Mensajes de Feedback --}}
    @if (session()->has('mensaje'))
        <div class="alert-message">
            {{ session('mensaje') }}
        </div>
    @endif

    {{-- Filtros Avanzados --}}
    <div class="card">
        <h2>Parámetros de búsqueda</h2>

        <div class="form-grid">
            <div class="form-group">
                <label>Buscar</label>
                <input type="text" wire:model.live.debounce.300ms="busqueda"
                    placeholder="Grupo, materia, aula o docente...">
            </div>

            <div class="form-group">
                <label>Gestión</label>
                <input type="text" wire:model.live.debounce.300ms="gestionFiltro" placeholder="Ej: 2026-1">
            </div>

            <div class="form-group">
                <label>Grupo</label>
                <select wire:model.live="grupoFiltro">
                    <option value="">Todos los grupos</option>
                    @foreach ($grupos as $grupo)
                        <option value="{{ $grupo->id }}">{{ $grupo->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>Docente</label>
                <select wire:model.live="docenteFiltro">
                    <option value="">Todos los docentes</option>
                    @foreach ($docentes as $docente)
                        <option value="{{ $docente->id }}">
                            {{ $docente->user->name ?? '' }} {{ $docente->user->apellido ?? '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>Materia</label>
                <select wire:model.live="materiaFiltro">
                    <option value="">Todas las materias</option>
                    @foreach ($materias as $materia)
                        <option value="{{ $materia->id }}">{{ $materia->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>Estado</label>
                <select wire:model.live="estadoFiltro">
                    <option value="">Todos los estados</option>
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
                <button wire:click="abrirCrear" class="btn">
                    + Nueva asignación
                </button>
                <button wire:click="generarAsignacionesAutomaticas"
                    wire:confirm="¿Desea generar automáticamente las asignaciones académicas?"
                    class="btn btn-secondary">
                    Generar asignaciones automáticamente
                </button>

                <button wire:click="limpiarFiltros" class="btn btn-danger-text" style="font-weight: 600;">
                    Limpiar filtros
                </button>
            </div>
        </div>
    </div>

    {{-- Tabla de Asignaciones --}}
    <div class="card">
        <div style="padding-bottom: 16px; border-bottom: 1px solid #f1f5f9;">
            <h2 style="font-size: 18px;">Lista de Asignaciones Académicas</h2>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th style="text-align:center; width:60px;">Nro</th>
                        <th>Docente</th>
                        <th>Grupo</th>
                        <th>Materia</th>
                        <th>Aula</th>
                        <th>Horario</th>
                        <th>Gestión</th>
                        <th style="text-align:center;">Estado</th>
                        <th style="text-align:right; width: 220px;">Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($asignaciones as $index => $asignacion)
                        <tr>
                            <td style="text-align:center; color:#94a3b8; font-weight:600;">
                                {{ $asignaciones->firstItem() + $index }}
                            </td>

                            <td>
                                <strong style="color: #0f172a; font-size: 14.5px;">
                                    {{ $asignacion->docente->user->name ?? '' }}
                                    {{ $asignacion->docente->user->apellido ?? '' }}
                                </strong>
                            </td>

                            <td>
                                <strong
                                    style="color: #334155;">{{ $asignacion->grupo->nombre ?? 'Sin grupo' }}</strong>
                                <span class="subtext">{{ $asignacion->grupo->carrera->nombre ?? '' }}</span>
                            </td>

                            <td>
                                <strong
                                    style="color: #1e40af;">{{ $asignacion->materia->nombre ?? 'Sin materia' }}</strong>
                            </td>

                            <td>
                                <strong style="color: #334155;">{{ $asignacion->aula->nombre ?? 'Sin aula' }}</strong>
                                <span class="subtext">{{ $asignacion->aula->ubicacion ?? '' }}</span>
                            </td>

                            <td>
                                <strong style="color: #0f172a;">{{ ucfirst($asignacion->horario->dia ?? '') }}</strong>
                                <span class="subtext" style="font-weight: 500; color: #16a34a;">
                                    {{ ucfirst($asignacion->horario->turno ?? '') }} |
                                    {{ $asignacion->horario->hora_inicio ?? '' }} -
                                    {{ $asignacion->horario->hora_final ?? '' }}
                                </span>
                            </td>

                            <td>
                                <span
                                    style="background: #f1f5f9; padding: 4px 8px; border-radius: 6px; font-size: 13px; font-weight: 600; color: #475569;">
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
                                    {{ $estadoActual }}
                                </span>
                            </td>

                            <td style="text-align:right;">
                                <div style="display: inline-flex; gap: 6px;">
                                    <button wire:click="editar({{ $asignacion->id }})" class="btn btn-secondary"
                                        style="padding: 6px 12px; font-size: 13px;">
                                        Editar
                                    </button>

                                    <button wire:click="cambiarEstado({{ $asignacion->id }})" class="btn btn-secondary"
                                        style="padding: 6px 12px; font-size: 13px;">
                                        {{ $asignacion->estado === 'activa' ? 'Inactivar' : 'Activar' }}
                                    </button>

                                    <button wire:click="eliminar({{ $asignacion->id }})"
                                        wire:confirm="¿Está seguro de eliminar esta asignación?"
                                        class="btn btn-danger-text"
                                        style="padding: 6px 12px; font-size: 13px; font-weight: 600;">
                                        Eliminar
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="text-align:center; padding:48px; color:#94a3b8;">
                                No existen asignaciones académicas registradas.
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

    {{-- Modal Fluido Integrado --}}
    @if ($modal)
        <div
            style="position: fixed; inset: 0; background: rgba(15, 23, 42, .4); backdrop-filter: blur(4px); display:flex; align-items:center; justify-content:center; z-index:50; transition: all 0.3s ease;">
            <div class="card"
                style="width:min(640px, 95vw); max-height:90vh; overflow-y:auto; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); border: none;">
                <div
                    style="display:flex; justify-content:space-between; align-items:flex-start; gap:16px; margin-bottom:20px; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px;">
                    <div>
                        <h2 style="font-size: 22px;">
                            {{ $asignacionId ? 'Editar Asignación Académica' : 'Nueva Asignación Académica' }}
                        </h2>
                        <p style="margin-top:4px;">Configure las cruces y variables de la asignación del CUP.</p>
                    </div>

                    <button wire:click="cerrarModal" class="btn btn-danger-text"
                        style="padding: 4px 8px; font-weight: 700;">
                        ✕
                    </button>
                </div>

                <form wire:submit.prevent="guardar">
                    <div class="form-grid" style="grid-template-columns: 1fr;">

                        <div class="form-group">
                            <label>Docente asignado</label>
                            <select wire:model="docente_id">
                                <option value="">Seleccione...</option>
                                @foreach ($docentes as $docente)
                                    <option value="{{ $docente->id }}">
                                        {{ $docente->user->name ?? '' }} {{ $docente->user->apellido ?? '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('docente_id')
                                <small
                                    style="color:#ef4444; font-weight: 600; margin-top: 4px;">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Grupo</label>
                            <select wire:model="grupo_id">
                                <option value="">Seleccione...</option>
                                @foreach ($grupos as $grupo)
                                    <option value="{{ $grupo->id }}">
                                        {{ $grupo->nombre }} @if ($grupo->carrera)
                                            - {{ $grupo->carrera->nombre }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('grupo_id')
                                <small
                                    style="color:#ef4444; font-weight: 600; margin-top: 4px;">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Materia</label>
                            <select wire:model="materia_id">
                                <option value="">Seleccione...</option>
                                @foreach ($materias as $materia)
                                    <option value="{{ $materia->id }}">{{ $materia->nombre }}</option>
                                @endforeach
                            </select>
                            @error('materia_id')
                                <small
                                    style="color:#ef4444; font-weight: 600; margin-top: 4px;">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Aula física</label>
                            <select wire:model="aula_id">
                                <option value="">Seleccione...</option>
                                @foreach ($aulas as $aula)
                                    <option value="{{ $aula->id }}">
                                        {{ $aula->nombre }} - Capacidad: {{ $aula->capacidad }}
                                    </option>
                                @endforeach
                            </select>
                            @error('aula_id')
                                <small
                                    style="color:#ef4444; font-weight: 600; margin-top: 4px;">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Horario y Turno</label>
                            <select wire:model="horario_id">
                                <option value="">Seleccione...</option>
                                @foreach ($horarios as $horario)
                                    <option value="{{ $horario->id }}">
                                        {{ ucfirst($horario->dia) }} | {{ ucfirst($horario->turno) }} |
                                        {{ $horario->hora_inicio }} - {{ $horario->hora_final }}
                                    </option>
                                @endforeach
                            </select>
                            @error('horario_id')
                                <small
                                    style="color:#ef4444; font-weight: 600; margin-top: 4px;">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Gestión académica</label>
                            <input type="text" wire:model="gestion" placeholder="Ej: 2026-1">
                            @error('gestion')
                                <small
                                    style="color:#ef4444; font-weight: 600; margin-top: 4px;">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Fecha de asignación oficial</label>
                            <input type="datetime-local" wire:model="fecha_asignacion">
                            @error('fecha_asignacion')
                                <small
                                    style="color:#ef4444; font-weight: 600; margin-top: 4px;">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Estado de la asignación</label>
                            <select wire:model="estado">
                                <option value="activa">Activa</option>
                                <option value="inactiva">Inactiva</option>
                                <option value="finalizada">Finalizada</option>
                            </select>
                            @error('estado')
                                <small
                                    style="color:#ef4444; font-weight: 600; margin-top: 4px;">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group form-full"
                            style="margin-top: 16px; display: flex; flex-direction: row; justify-content: flex-end; gap: 10px;">
                            <span wire:loading wire:target="guardar"
                                style="font-size: 14px; color: #64748b; align-self: center;">
                                Procesando datos...
                            </span>

                            <button type="button" wire:click="cerrarModal" class="btn btn-secondary">
                                Cancelar
                            </button>

                            <button type="submit" class="btn" wire:loading.attr="disabled">
                                Guardar asignación
                            </button>
                        </div>

                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
