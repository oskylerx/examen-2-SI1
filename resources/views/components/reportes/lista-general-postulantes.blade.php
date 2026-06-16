<?php

use App\Models\Postulante;
use App\Models\Carrera;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $busqueda = '';
    public string $estadoInscripcion = '';
    public string $resultadoFinal = '';
    public string $carrera_id = '';
    public int $porPagina = 10;
    public string $tipoLista = 'general';

    public function updatedBusqueda() { $this->resetPage(); }
    public function updatedEstadoInscripcion() { $this->resetPage(); }
    public function updatedResultadoFinal() { $this->resetPage(); }
    public function updatedCarreraId() { $this->resetPage(); }
    public function updatedPorPagina() { $this->resetPage(); }

    public function listaGeneral()
    {
        $this->tipoLista = 'general';
        $this->resultadoFinal = '';
        $this->estadoInscripcion = '';
        $this->resetPage();
    }

    public function listaAceptadosCupo()
    {
        $this->tipoLista = 'aceptados_con_cupo';
        $this->resultadoFinal = 'aceptados_con_cupo';
        $this->estadoInscripcion = '';
        $this->resetPage();
    }

    public function listaPrimeraOpcion()
    {
        $this->tipoLista = 'primera_opcion';
        $this->resultadoFinal = 'primera_opcion';
        $this->estadoInscripcion = '';
        $this->resetPage();
    }

    public function listaSegundaOpcion()
    {
        $this->tipoLista = 'segunda_opcion';
        $this->resultadoFinal = 'segunda_opcion';
        $this->estadoInscripcion = '';
        $this->resetPage();
    }

    public function listaSinCupo()
    {
        $this->tipoLista = 'aprobado_sin_cupo';
        $this->resultadoFinal = 'aprobado_sin_cupo';
        $this->estadoInscripcion = '';
        $this->resetPage();
    }

    public function listaReprobados()
    {
        $this->tipoLista = 'reprobados';
        $this->resultadoFinal = 'reprobado';
        $this->estadoInscripcion = '';
        $this->resetPage();
    }

    public function limpiarFiltros()
    {
        $this->busqueda = '';
        $this->estadoInscripcion = '';
        $this->resultadoFinal = '';
        $this->carrera_id = '';
        $this->porPagina = 10;
        $this->tipoLista = 'general';

        $this->resetPage();
    }

    public function obtenerPostulantes()
    {
        return Postulante::query()
            ->with([
                'user',
                'grupo',
                'primeraOpcionCarrera',
                'segundaOpcionCarrera',
                'asignacionCupo.carrera',
                'pagos',
                'documentos',
            ]);
    }

    public function filtrarPostulantes()
    {
        $query = $this->obtenerPostulantes();

        if ($this->estadoInscripcion !== '') {
            $query->where('estado_inscripcion', $this->estadoInscripcion);
        }

        if ($this->resultadoFinal !== '') {
            if ($this->resultadoFinal === 'aceptados_con_cupo') {
                $query->whereHas('asignacionCupo', function ($q) {
                    $q->whereIn('estado', ['primera_opcion', 'segunda_opcion']);
                });
            } elseif ($this->resultadoFinal === 'sin_resultado') {
                $query->whereDoesntHave('asignacionCupo');
            } else {
                $query->whereHas('asignacionCupo', function ($q) {
                    $q->where('estado', $this->resultadoFinal);
                });
            }
        }

        if ($this->carrera_id !== '') {
            $query->where(function ($q) {
                $q->where('primera_opcion_carrera_id', $this->carrera_id)
                    ->orWhere('segunda_opcion_carrera_id', $this->carrera_id)
                    ->orWhereHas('asignacionCupo', function ($asignacionQuery) {
                        $asignacionQuery->where('carrera_id', $this->carrera_id);
                    });
            });
        }

        if ($this->busqueda !== '') {
            $buscar = '%' . $this->busqueda . '%';

            $query->whereHas('user', function ($userQuery) use ($buscar) {
                $userQuery->where('name', 'ILIKE', $buscar)
                    ->orWhere('apellido', 'ILIKE', $buscar)
                    ->orWhere('ci', 'ILIKE', $buscar)
                    ->orWhere('username', 'ILIKE', $buscar)
                    ->orWhere('telefono', 'ILIKE', $buscar)
                    ->orWhere('email', 'ILIKE', $buscar);
            });
        }

        return $query
            ->orderByRaw('(SELECT posicion_ranking FROM asignacion_cupo WHERE asignacion_cupo.postulante_id = postulante.id) ASC NULLS LAST')
            ->orderBy('fecha_registro', 'desc')
            ->orderBy('id', 'desc');
    }

    public function etiquetaInscripcion($estado)
    {
        return match ($estado) {
            'pendiente' => 'Pendiente',
            'observado' => 'Observado',
            'aceptado' => 'Aceptado',
            'rechazado' => 'Rechazado',
            default => ucfirst($estado ?? 'Pendiente'),
        };
    }

    public function badgeInscripcion($estado)
    {
        return match ($estado) {
            'aceptado' => 'badge-aprobado',
            'rechazado' => 'badge-rechazado',
            'observado' => 'badge-pendiente',
            default => 'badge-pendiente',
        };
    }

    public function etiquetaResultado($estado)
    {
        return match ($estado) {
            'primera_opcion' => 'Aceptado 1ra opción',
            'segunda_opcion' => 'Aceptado 2da opción',
            'aprobado_sin_cupo' => 'Aprobado sin cupo',
            'reprobado' => 'Reprobado',
            'anulado' => 'Anulado',
            default => 'Resultado pendiente',
        };
    }

    public function badgeResultado($estado)
    {
        return match ($estado) {
            'primera_opcion', 'segunda_opcion' => 'badge-aprobado',
            'aprobado_sin_cupo', 'reprobado', 'anulado' => 'badge-rechazado',
            default => 'badge-pendiente',
        };
    }

    public function obtenerEstadoDocumento($postulante)
    {
        $documentosRequeridos = [
            'titulo_bachiller',
            'cedula_identidad',
            'boletin_sexto',
            'comprobante_pago',
        ];

        $validados = $postulante->documentos
            ->whereIn('tipo', $documentosRequeridos)
            ->where('estado', 'validado')
            ->count();

        return $validados . '/' . count($documentosRequeridos);
    }

    public function obtenerEstadoPago($postulante)
    {
        $pago = $postulante->pagos
            ->sortByDesc('fecha_pago')
            ->first();

        return $pago->estado ?? 'sin_pago';
    }

    public function exportarLista($tipo)
    {
        if ($tipo === 'pdf') {
            $this->dispatch('imprimir-lista');
            return;
        }

        if ($tipo === 'excel') {
            session()->flash('mensaje', 'La exportación a Excel nativo se implementará después.');
            return;
        }

        $postulantes = $this->filtrarPostulantes()->get();
        $nombreArchivo = 'reporte_postulantes_' . $this->tipoLista . '_' . date('Ymd_His');

        if ($tipo === 'csv') {
            return response()->streamDownload(function () use ($postulantes) {
                $handle = fopen('php://output', 'w');
                fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

                fputcsv($handle, [
                    'Nro',
                    'Ranking',
                    'Nombre Completo',
                    'Usuario',
                    'CI',
                    'Correo',
                    'Telefono',
                    'Grupo',
                    'Primera Opcion',
                    'Segunda Opcion',
                    'Carrera Asignada',
                    'Promedio Final',
                    'Resultado Final',
                    'Estado Inscripcion',
                    'Pago',
                    'Documentos',
                    'Fecha Registro',
                ], ';');

                foreach ($postulantes as $index => $p) {
                    $asignacion = $p->asignacionCupo;
                    $resultado = $this->etiquetaResultado($asignacion->estado ?? null);

                    fputcsv($handle, [
                        $index + 1,
                        $asignacion->posicion_ranking ?? '-',
                        ($p->user->name ?? '') . ' ' . ($p->user->apellido ?? ''),
                        $p->user->username ?? '',
                        $p->user->ci ?? '',
                        $p->user->email ?? '',
                        $p->user->telefono ?? '',
                        $p->grupo->nombre ?? 'Sin grupo',
                        $p->primeraOpcionCarrera->nombre ?? 'No asignada',
                        $p->segundaOpcionCarrera->nombre ?? 'No asignada',
                        $asignacion->carrera->nombre ?? 'Sin carrera asignada',
                        $asignacion->promedio_final ?? '-',
                        $resultado,
                        $this->etiquetaInscripcion($p->estado_inscripcion),
                        $this->obtenerEstadoPago($p),
                        $this->obtenerEstadoDocumento($p),
                        $p->fecha_registro ? \Carbon\Carbon::parse($p->fecha_registro)->format('d/m/Y') : '-',
                    ], ';');
                }

                fclose($handle);
            }, $nombreArchivo . '.csv', [
                'Content-Type' => 'text/csv; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="' . $nombreArchivo . '.csv"',
            ]);
        }

        if ($tipo === 'word') {
            return response()->streamDownload(function () use ($postulantes) {
                $limpiar = fn ($valor) => e((string) ($valor ?? '-'));

                echo "<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>";
                echo "<head><meta charset='utf-8'>";
                echo "<style>
                    @page SeccionHorizontal {
                        size: 11in 8.5in;
                        mso-page-orientation: landscape;
                        margin: 0.45in;
                    }
                    div.SeccionHorizontal { page: SeccionHorizontal; }
                    body { font-family: Arial, sans-serif; color: #333333; }
                    h2 { color: #0f172a; font-size: 17pt; margin-bottom: 5px; }
                    p { color: #64748b; font-size: 9.5pt; margin-top: 0; margin-bottom: 16px; }
                    table { width: 100%; border-collapse: collapse; }
                    th, td { border: 1px solid #cbd5e1; padding: 6px; font-size: 8.5pt; text-align: left; }
                    th { background-color: #f1f5f9; color: #475569; font-weight: bold; text-transform: uppercase; font-size: 7.5pt; }
                    tr:nth-child(even) { background-color: #f8fafc; }
                    .center { text-align: center; }
                    .right { text-align: right; }
                </style></head>";

                echo "<body><div class='SeccionHorizontal'>";
                echo "<h2>REPORTE DE POSTULANTES - " . strtoupper($limpiar($this->tipoLista)) . "</h2>";
                echo "<p>Fecha de generación: " . date('d/m/Y H:i') . " | Total registros: " . count($postulantes) . "</p>";

                echo "<table>";
                echo "<thead><tr>
                    <th class='center'>Nro</th>
                    <th class='center'>Ranking</th>
                    <th>Nombre</th>
                    <th>CI</th>
                    <th>Grupo</th>
                    <th>1ra Opción</th>
                    <th>2da Opción</th>
                    <th>Carrera Asignada</th>
                    <th class='center'>Promedio</th>
                    <th class='center'>Resultado</th>
                    <th class='center'>Inscripción</th>
                    <th class='center'>Docs</th>
                </tr></thead><tbody>";

                foreach ($postulantes as $index => $p) {
                    $asignacion = $p->asignacionCupo;

                    echo "<tr>";
                    echo "<td class='center'>" . ($index + 1) . "</td>";
                    echo "<td class='center'>" . $limpiar($asignacion->posicion_ranking ?? '-') . "</td>";
                    echo "<td><strong>" . $limpiar(($p->user->name ?? '') . ' ' . ($p->user->apellido ?? '')) . "</strong></td>";
                    echo "<td>" . $limpiar($p->user->ci ?? '-') . "</td>";
                    echo "<td>" . $limpiar($p->grupo->nombre ?? 'Sin grupo') . "</td>";
                    echo "<td>" . $limpiar($p->primeraOpcionCarrera->nombre ?? 'No asignada') . "</td>";
                    echo "<td>" . $limpiar($p->segundaOpcionCarrera->nombre ?? 'No asignada') . "</td>";
                    echo "<td>" . $limpiar($asignacion->carrera->nombre ?? 'Sin carrera asignada') . "</td>";
                    echo "<td class='center'>" . $limpiar($asignacion->promedio_final ?? '-') . "</td>";
                    echo "<td class='center'>" . $limpiar($this->etiquetaResultado($asignacion->estado ?? null)) . "</td>";
                    echo "<td class='center'>" . $limpiar($this->etiquetaInscripcion($p->estado_inscripcion)) . "</td>";
                    echo "<td class='center'>" . $limpiar($this->obtenerEstadoDocumento($p)) . "</td>";
                    echo "</tr>";
                }

                echo "</tbody></table>";
                echo "</div></body></html>";
            }, $nombreArchivo . '.doc', [
                'Content-Type' => 'application/vnd.ms-word',
                'Content-Disposition' => 'attachment; filename="' . $nombreArchivo . '.doc"',
            ]);
        }
    }

    public function with()
    {
        $baseQuery = $this->filtrarPostulantes();

        $postulantes = (clone $baseQuery)->paginate($this->porPagina);

        $totalPostulantes = (clone $baseQuery)->count();

        $totalAceptadosCupo = (clone $baseQuery)
            ->whereHas('asignacionCupo', function ($q) {
                $q->whereIn('estado', ['primera_opcion', 'segunda_opcion']);
            })
            ->count();

        $totalPrimera = (clone $baseQuery)
            ->whereHas('asignacionCupo', function ($q) {
                $q->where('estado', 'primera_opcion');
            })
            ->count();

        $totalSegunda = (clone $baseQuery)
            ->whereHas('asignacionCupo', function ($q) {
                $q->where('estado', 'segunda_opcion');
            })
            ->count();

        $totalSinCupo = (clone $baseQuery)
            ->whereHas('asignacionCupo', function ($q) {
                $q->where('estado', 'aprobado_sin_cupo');
            })
            ->count();

        $totalReprobados = (clone $baseQuery)
            ->whereHas('asignacionCupo', function ($q) {
                $q->where('estado', 'reprobado');
            })
            ->count();

        $totalPendientesResultado = (clone $baseQuery)
            ->whereDoesntHave('asignacionCupo')
            ->count();

        return [
            'postulantes' => $postulantes,
            'carreras' => Carrera::orderBy('nombre')->get(),
            'totalPostulantes' => $totalPostulantes,
            'totalAceptadosCupo' => $totalAceptadosCupo,
            'totalPrimera' => $totalPrimera,
            'totalSegunda' => $totalSegunda,
            'totalSinCupo' => $totalSinCupo,
            'totalReprobados' => $totalReprobados,
            'totalPendientesResultado' => $totalPendientesResultado,
        ];
    }
};

?>

<div class="native-container">
    <style>
        .native-container { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; color: #334155; padding: 24px; background-color: #f8fafc; min-height: 100vh; box-sizing: border-box; }
        .native-container * { box-sizing: border-box; }
        .card { background: #ffffff; padding: 24px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 24px; }
        .card h2 { margin: 0 0 8px 0; font-size: 20px; font-weight: 700; color: #0f172a; }
        .card p { margin: 0; color: #64748b; font-size: 14px; line-height: 1.5; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .stat { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px; }
        .stat span { display:block; color:#64748b; font-size:12px; font-weight:700; text-transform:uppercase; margin-bottom:6px; }
        .stat strong { color:#0f172a; font-size:24px; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-top: 20px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group label { font-size: 12px; font-weight: 600; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; }
        .form-group input, .form-group select { padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; background-color: #ffffff; color: #334155; width: 100%; }
        .actions-bar { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 16px; margin-top: 24px; padding-top: 20px; border-top: 1px solid #f1f5f9; }
        .btn-group { display: flex; flex-wrap: wrap; gap: 8px; }
        .btn { padding: 10px 16px; font-size: 13px; font-weight: 600; border-radius: 8px; cursor: pointer; transition: all 0.2s; border: 1px solid transparent; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
        .btn-secondary { background-color: #ffffff; color: #475569; border-color: #cbd5e1; }
        .btn-word { background-color: #1e3a8a; color: #ffffff; }
        .btn-csv { background-color: #0f766e; color: #ffffff; }
        .btn-excel { background-color: #16a34a; color: #ffffff; }
        .btn-active { background-color: #0f172a; color: #ffffff; border-color: #0f172a; }
        .btn-danger-text { color: #ef4444; border-color: transparent; background: transparent; }
        .table-responsive { overflow-x: auto; margin-top: 16px; border-radius: 8px; border: 1px solid #e2e8f0; background: #ffffff; }
        .table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
        .table th { background-color: #f1f5f9; padding: 14px 16px; font-weight: 600; color: #475569; font-size: 12px; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; white-space: nowrap; }
        .table td { padding: 14px 16px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
        .badge { padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 700; display: inline-block; text-transform: uppercase; border: 1px solid transparent; letter-spacing: 0.5px; }
        .badge-pendiente { background-color: #fffbeb; color: #b45309; border-color: #fde68a; }
        .badge-aprobado { background-color: #f0fdf4; color: #166534; border-color: #bbf7d0; }
        .badge-rechazado { background-color: #fef2f2; color: #991b1b; border-color: #fecaca; }
        .loading-badge { font-size: 13px; color: #2563eb; background: #eff6ff; padding: 6px 14px; border-radius: 20px; font-weight: 600; border: 1px solid #bfdbfe; }
        .alert-message { background-color: #eff6ff; border-left: 4px solid #3b82f6; padding: 16px; border-radius: 0 8px 8px 0; margin-bottom: 24px; color: #1e40af; font-size: 14px; font-weight: 500; }
        @media print {
            body * { visibility: hidden; }
            #area-imprimir, #area-imprimir * { visibility: visible; }
            #area-imprimir { position: absolute; left: 0; top: 0; width: 100%; border: none; box-shadow: none; }
        }
    </style>

    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
            <div>
                <h2>Reporte de Postulantes</h2>
                <p>
                    Consulte la lista general, aceptados con cupo, aprobados sin cupo, reprobados y resultados finales de admisión.
                </p>
            </div>

            <div wire:loading class="loading-badge">
                Actualizando datos...
            </div>
        </div>
    </div>

    @if (session()->has('mensaje'))
        <div class="alert-message">
            • {{ session('mensaje') }}
        </div>
    @endif

    <div class="grid">
        <div class="stat">
            <span>Total filtrado</span>
            <strong>{{ $totalPostulantes }}</strong>
        </div>

        <div class="stat">
            <span>Aceptados con cupo</span>
            <strong>{{ $totalAceptadosCupo }}</strong>
        </div>

        <div class="stat">
            <span>Primera opción</span>
            <strong>{{ $totalPrimera }}</strong>
        </div>

        <div class="stat">
            <span>Segunda opción</span>
            <strong>{{ $totalSegunda }}</strong>
        </div>

        <div class="stat">
            <span>Aprobados sin cupo</span>
            <strong>{{ $totalSinCupo }}</strong>
        </div>

        <div class="stat">
            <span>Reprobados</span>
            <strong>{{ $totalReprobados }}</strong>
        </div>
    </div>

    <div class="card">
        <h2>Parámetros del Reporte</h2>

        <div class="form-grid">
            <div class="form-group">
                <label>Buscar postulante</label>
                <input type="text" wire:model.live.debounce.300ms="busqueda" placeholder="CI, usuario, nombre, correo o celular...">
            </div>

            <div class="form-group">
                <label>Estado de inscripción</label>
                <select wire:model.live="estadoInscripcion">
                    <option value="">Todos</option>
                    <option value="pendiente">Pendiente</option>
                    <option value="observado">Observado</option>
                    <option value="aceptado">Aceptado</option>
                    <option value="rechazado">Rechazado</option>
                </select>
            </div>

            <div class="form-group">
                <label>Resultado final</label>
                <select wire:model.live="resultadoFinal">
                    <option value="">Todos</option>
                    <option value="aceptados_con_cupo">Aceptados con cupo</option>
                    <option value="primera_opcion">Aceptado en primera opción</option>
                    <option value="segunda_opcion">Aceptado en segunda opción</option>
                    <option value="aprobado_sin_cupo">Aprobado sin cupo</option>
                    <option value="reprobado">Reprobado</option>
                    <option value="anulado">Anulado</option>
                    <option value="sin_resultado">Sin resultado generado</option>
                </select>
            </div>

            <div class="form-group">
                <label>Carrera relacionada</label>
                <select wire:model.live="carrera_id">
                    <option value="">Todas las carreras</option>
                    @foreach ($carreras as $carrera)
                        <option value="{{ $carrera->id }}">
                            {{ $carrera->codigo_carrera ?? '' }} - {{ $carrera->nombre }}
                        </option>
                    @endforeach
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
                <button wire:click="listaGeneral" class="btn btn-secondary {{ $tipoLista === 'general' ? 'btn-active' : '' }}">General</button>
                <button wire:click="listaAceptadosCupo" class="btn btn-secondary {{ $tipoLista === 'aceptados_con_cupo' ? 'btn-active' : '' }}">Aceptados con cupo</button>
                <button wire:click="listaPrimeraOpcion" class="btn btn-secondary {{ $tipoLista === 'primera_opcion' ? 'btn-active' : '' }}">1ra opción</button>
                <button wire:click="listaSegundaOpcion" class="btn btn-secondary {{ $tipoLista === 'segunda_opcion' ? 'btn-active' : '' }}">2da opción</button>
                <button wire:click="listaSinCupo" class="btn btn-secondary {{ $tipoLista === 'aprobado_sin_cupo' ? 'btn-active' : '' }}">Sin cupo</button>
                <button wire:click="listaReprobados" class="btn btn-secondary {{ $tipoLista === 'reprobados' ? 'btn-active' : '' }}">Reprobados</button>
                <button wire:click="limpiarFiltros" class="btn btn-danger-text">Limpiar filtros</button>
            </div>

            <div class="btn-group">
                <button wire:click="exportarLista('pdf')" class="btn btn-secondary">Imprimir</button>
                <button wire:click="exportarLista('word')" class="btn btn-word">Word</button>
                <button wire:click="exportarLista('csv')" class="btn btn-csv">CSV</button>
                <button wire:click="exportarLista('excel')" class="btn btn-excel">Excel</button>
            </div>
        </div>
    </div>

    <div class="card" id="area-imprimir">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap; padding-bottom:16px; border-bottom:1px solid #e2e8f0;">
            <div>
                <h2 style="margin:0; font-size:18px;">Resultado General de Postulantes</h2>
                <p style="margin-top:4px;">
                    Filtros aplicados para un total de:
                    <strong style="color:#2563eb;">{{ $totalPostulantes }}</strong> registros.
                </p>
            </div>

            <div style="font-size:12px; background:#f1f5f9; padding:6px 14px; border-radius:6px; font-weight:700; text-transform:uppercase; color:#475569;">
                Segmento: <span style="color:#2563eb;">{{ str_replace('_', ' ', $tipoLista) }}</span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th style="text-align:center;">Nro</th>
                        <th style="text-align:center;">Ranking</th>
                        <th>Postulante</th>
                        <th>CI / Usuario</th>
                        <th>Grupo</th>
                        <th>Opciones de carrera</th>
                        <th>Carrera asignada</th>
                        <th style="text-align:center;">Promedio</th>
                        <th style="text-align:center;">Resultado final</th>
                        <th style="text-align:center;">Inscripción</th>
                        <th style="text-align:center;">Pago</th>
                        <th style="text-align:center;">Docs</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($postulantes as $index => $postulante)
                        @php
                            $asignacion = $postulante->asignacionCupo;
                            $estadoResultado = $asignacion->estado ?? null;
                            $estadoInscripcionActual = $postulante->estado_inscripcion ?? 'pendiente';
                            $estadoPago = $this->obtenerEstadoPago($postulante);
                        @endphp

                        <tr>
                            <td style="text-align:center; color:#94a3b8; font-weight:600;">
                                {{ $postulantes->firstItem() + $index }}
                            </td>

                            <td style="text-align:center;">
                                {{ $asignacion->posicion_ranking ?? '-' }}
                            </td>

                            <td>
                                <strong style="color:#0f172a;">
                                    {{ $postulante->user->name ?? '' }}
                                    {{ $postulante->user->apellido ?? '' }}
                                </strong>
                                <br>
                                <small>{{ $postulante->user->email ?? 'Sin correo' }}</small>
                            </td>

                            <td>
                                <strong>{{ $postulante->user->ci ?? 'Sin CI' }}</strong>
                                <br>
                                <small>{{ $postulante->user->username ?? 'Sin usuario' }}</small>
                            </td>

                            <td>
                                {{ $postulante->grupo->nombre ?? 'Sin grupo' }}
                            </td>

                            <td>
                                <div>
                                    <span style="color:#94a3b8; font-size:11px; font-weight:bold;">1°</span>
                                    {{ $postulante->primeraOpcionCarrera->nombre ?? 'No asignada' }}
                                </div>
                                <div style="font-size:12px; color:#64748b; margin-top:3px;">
                                    <span style="color:#94a3b8; font-size:11px;">2°</span>
                                    {{ $postulante->segundaOpcionCarrera->nombre ?? 'No asignada' }}
                                </div>
                            </td>

                            <td>
                                @if ($asignacion && $asignacion->carrera)
                                    <strong>{{ $asignacion->carrera->nombre }}</strong>
                                @else
                                    <span style="color:#94a3b8;">Sin carrera asignada</span>
                                @endif
                            </td>

                            <td style="text-align:center;">
                                <strong>{{ $asignacion->promedio_final ?? '-' }}</strong>
                            </td>

                            <td style="text-align:center;">
                                <span class="badge {{ $this->badgeResultado($estadoResultado) }}">
                                    {{ $this->etiquetaResultado($estadoResultado) }}
                                </span>
                            </td>

                            <td style="text-align:center;">
                                <span class="badge {{ $this->badgeInscripcion($estadoInscripcionActual) }}">
                                    {{ $this->etiquetaInscripcion($estadoInscripcionActual) }}
                                </span>
                            </td>

                            <td style="text-align:center;">
                                @if ($estadoPago === 'pagado')
                                    <span class="badge badge-aprobado">Pagado</span>
                                @elseif ($estadoPago === 'observado')
                                    <span class="badge badge-pendiente">Observado</span>
                                @elseif ($estadoPago === 'rechazado')
                                    <span class="badge badge-rechazado">Rechazado</span>
                                @else
                                    <span class="badge badge-pendiente">{{ $estadoPago }}</span>
                                @endif
                            </td>

                            <td style="text-align:center;">
                                <strong>{{ $this->obtenerEstadoDocumento($postulante) }}</strong>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" style="text-align:center; padding:48px; color:#94a3b8; font-size:14px;">
                                Ningún registro coincide con los filtros establecidos.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top:20px;">
            {{ $postulantes->links() }}
        </div>
    </div>

    @script
    <script>
        $wire.on('imprimir-lista', () => {
            window.print();
        });
    </script>
    @endscript
</div>