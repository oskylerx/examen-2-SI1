<?php

use App\Models\Postulante;
use App\Models\Carrera;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $busqueda = '';
    public string $estado = '';
    public string $carrera_id = '';
    public int $porPagina = 10;
    public string $tipoLista = 'general';

    public function updatedBusqueda() { $this->resetPage(); }
    public function updatedEstado() { $this->resetPage(); }
    public function updatedCarreraId() { $this->resetPage(); }
    public function updatedPorPagina() { $this->resetPage(); }

    public function generarListaPostulantes()
    {
        $this->resetPage();
    }

    public function listaGeneral()
    {
        $this->tipoLista = 'general';
        $this->estado = '';
        $this->resetPage();
    }

    public function listaAprobados()
    {
        $this->tipoLista = 'aprobados';
        $this->estado = 'aprobado';
        $this->resetPage();
    }

    public function listaReprobados()
    {
        $this->tipoLista = 'reprobados';
        $this->estado = 'rechazado';
        $this->resetPage();
    }

    public function limpiarFiltros()
    {
        $this->reset(['busqueda', 'estado', 'carrera_id', 'porPagina', 'tipoLista']);
        $this->resetPage();
    }

    public function obtenerPostulantes()
    {
        return Postulante::query()
            ->with([
                'user',
                'primeraOpcionCarrera',
                'segundaOpcionCarrera',
            ]);
    }

    public function filtrarPostulantes()
    {
        $query = $this->obtenerPostulantes();

        if ($this->estado !== '') {
            $query->where('estado_inscripcion', $this->estado);
        }

        if ($this->carrera_id !== '') {
            $query->where(function ($q) {
                $q->where('primera_opcion_carrera_id', $this->carrera_id)
                  ->orWhere('segunda_opcion_carrera_id', $this->carrera_id);
            });
        }

        if ($this->busqueda !== '') {
            $buscar = '%' . $this->busqueda . '%';

            $query->whereHas('user', function ($userQuery) use ($buscar) {
                $userQuery->where('name', 'ILIKE', $buscar)
                    ->orWhere('apellido', 'ILIKE', $buscar)
                    ->orWhere('ci', 'ILIKE', $buscar)
                    ->orWhere('telefono', 'ILIKE', $buscar)
                    ->orWhere('email', 'ILIKE', $buscar);
            });
        }

        return $query
            ->orderBy('fecha_registro', 'desc')
            ->orderBy('id', 'desc');
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
        $nombreArchivo = 'reporte_' . $this->tipoLista . '_' . date('Ymd_His');

        // EXPORTACIÓN NATIVA A CSV
        if ($tipo === 'csv') {
            return response()->streamDownload(function () use ($postulantes) {
                $handle = fopen('php://output', 'w');
                fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
                
                fputcsv($handle, ['Nro', 'Nombre Completo', 'CI/Documento', 'Correo', 'Telefono', 'Primera Opcion', 'Segunda Opcion', 'Estado', 'Fecha Registro'], ';');
                
                foreach ($postulantes as $index => $p) {
                    fputcsv($handle, [
                        $index + 1,
                        ($p->user->name ?? '') . ' ' . ($p->user->apellido ?? ''),
                        $p->user->ci ?? '',
                        $p->user->email ?? '',
                        $p->user->telefono ?? '',
                        $p->primeraOpcionCarrera->nombre ?? 'No asignada',
                        $p->segundaOpcionCarrera->nombre ?? 'No asignada',
                        $p->estado_inscripcion ?? 'pendiente',
                        $p->fecha_registro ? \Carbon\Carbon::parse($p->fecha_registro)->format('d/m/Y') : '-'
                    ], ';');
                }
                fclose($handle);
            }, $nombreArchivo . '.csv', [
                'Content-Type' => 'text/csv; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="' . $nombreArchivo . '.csv"',
            ]);
        }

        // EXPORTACIÓN NATIVA A WORD EN HORIZONTAL (LANDSCAPE)
        if ($tipo === 'word') {
            return response()->streamDownload(function () use ($postulantes) {
                echo "<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>";
                echo "<head><meta charset='utf-8'>";
                echo "";
                echo "<style>
                    @page SeccionHorizontal {
                        size: 11in 8.5in; /* Dimensiones de hoja Carta Horizontal */
                        mso-page-orientation: landscape;
                        margin: 0.5in 0.5in 0.5in 0.5in;
                    }
                    div.SeccionHorizontal { page: SeccionHorizontal; }
                    body { font-family: 'Arial', sans-serif; color: #333333; }
                    h2 { color: #0f172a; font-size: 18pt; margin-bottom: 5px; }
                    p { color: #64748b; font-size: 10pt; margin-top: 0; margin-bottom: 20px; }
                    table { width: 100%; border-collapse: collapse; }
                    th, td { border: 1px solid #cbd5e1; padding: 8px; font-size: 9.5pt; text-align: left; }
                    th { background-color: #f1f5f9; color: #475569; font-weight: bold; text-transform: uppercase; font-size: 8.5pt; }
                    tr:nth-child(even) { background-color: #f8fafc; }
                    .center { text-align: center; }
                    .right { text-align: right; }
                    .badge { font-weight: bold; padding: 2px 6px; border-radius: 4px; font-size: 8.5pt; }
                </style></head>";
                echo "<body>";
                
                // Forzamos el uso de la sección configurada en el CSS
                echo "<div class='SeccionHorizontal'>";
                
                echo "<h2>REPORTE DE POSTULANTES - LISTA " . strtoupper($this->tipoLista) . "</h2>";
                echo "<p>Fecha de generación: " . date('d/m/Y H:i') . " | Total registros: " . count($postulantes) . "</p>";
                
                echo "<table>";
                echo "<thead><tr>
                        <th style='width: 5%;' class='center'>Nro</th>
                        <th style='width: 20%;'>Nombre Completo</th>
                        <th style='width: 10%;'>CI</th>
                        <th style='width: 18%;'>Correo</th>
                        <th style='width: 10%;'>Teléfono</th>
                        <th style='width: 15%;'>1ra Opción</th>
                        <th style='width: 12%;'>2da Opción</th>
                        <th style='width: 10%;' class='center'>Estado</th>
                        <th style='width: 10%;' class='right'>Registro</th>
                     </tr></thead>";
                echo "<tbody>";
                
                foreach ($postulantes as $index => $p) {
                    echo "<tr>";
                    echo "<td class='center'>" . ($index + 1) . "</td>";
                    echo "<td><strong>" . ($p->user->name ?? '') . " " . ($p->user->apellido ?? '') . "</strong></td>";
                    echo "<td>" . ($p->user->ci ?? '-') . "</td>";
                    echo "<td>" . ($p->user->email ?? '-') . "</td>";
                    echo "<td>" . ($p->user->telefono ?? '-') . "</td>";
                    echo "<td>" . ($p->primeraOpcionCarrera->nombre ?? 'No asignada') . "</td>";
                    echo "<td>" . ($p->segundaOpcionCarrera->nombre ?? 'No asignada') . "</td>";
                    echo "<td class='center'><span class='badge'>" . strtoupper($p->estado_inscripcion ?? 'pendiente') . "</span></td>";
                    echo "<td class='right'>" . ($p->fecha_registro ? \Carbon\Carbon::parse($p->fecha_registro)->format('d/m/Y') : '-') . "</td>";
                    echo "</tr>";
                }
                
                echo "</tbody></table>";
                echo "</div>"; // Cierre de la sección horizontal
                echo "</body></html>";
            }, $nombreArchivo . '.doc', [
                'Content-Type' => 'application/vnd.ms-word',
                'Content-Disposition' => 'attachment; filename="' . $nombreArchivo . '.doc"',
            ]);
        }
    }

    public function with()
    {
        $postulantes = $this->filtrarPostulantes()
            ->paginate($this->porPagina);

        return [
            'postulantes' => $postulantes,
            'carreras' => Carrera::orderBy('nombre')->get(),
            'totalPostulantes' => $postulantes->total(),
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
        
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-top: 20px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group label { font-size: 12px; font-weight: 600; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; }
        .form-group input, .form-group select { padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; background-color: #ffffff; color: #334155; width: 100%; transition: border-color 0.2s, box-shadow 0.2s; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.15); }
        
        .actions-bar { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 16px; margin-top: 24px; padding-top: 20px; border-top: 1px solid #f1f5f9; }
        .btn-group { display: flex; flex-wrap: wrap; gap: 8px; }
        .btn { padding: 10px 16px; font-size: 13px; font-weight: 600; border-radius: 8px; cursor: pointer; transition: all 0.2s; border: 1px solid transparent; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
        
        .btn-primary { background-color: #2563eb; color: #ffffff; }
        .btn-primary:hover { background-color: #1d4ed8; }
        .btn-secondary { background-color: #ffffff; color: #475569; border-color: #cbd5e1; }
        .btn-secondary:hover { background-color: #f8fafc; border-color: #94a3b8; }
        
        .btn-word { background-color: #1e3a8a; color: #ffffff; }
        .btn-word:hover { background-color: #172554; }
        .btn-csv { background-color: #0f766e; color: #ffffff; }
        .btn-csv:hover { background-color: #115e59; }
        .btn-excel { background-color: #16a34a; color: #ffffff; }
        .btn-excel:hover { background-color: #15803d; }
        
        .btn-active { background-color: #0f172a; color: #ffffff; border-color: #0f172a; }
        .btn-danger-text { color: #ef4444; border-color: transparent; background: transparent; }
        .btn-danger-text:hover { background: #fef2f2; }

        .view-icon { width: 14px !important; height: 14px !important; min-width: 14px; max-width: 14px; min-height: 14px; max-height: 14px; display: inline-block; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; vertical-align: middle; }
        
        .table-responsive { overflow-x: auto; margin-top: 16px; border-radius: 8px; border: 1px solid #e2e8f0; background: #ffffff; }
        .table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
        .table th { background-color: #f1f5f9; padding: 14px 16px; font-weight: 600; color: #475569; font-size: 12px; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; }
        .table td { padding: 14px 16px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
        .table tr:hover { background-color: #f8fafc; }
        
        .badge { padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 700; display: inline-block; text-transform: uppercase; border: 1px solid transparent; letter-spacing: 0.5px; }
        .badge-pendiente { background-color: #fffbeb; color: #b45309; border-color: #fde68a; }
        .badge-aprobado { background-color: #f0fdf4; color: #166534; border-color: #bbf7d0; }
        .badge-rechazado { background-color: #fef2f2; color: #991b1b; border-color: #fecaca; }
        
        .loading-badge { font-size: 13px; color: #2563eb; background: #eff6ff; padding: 6px 14px; border-radius: 20px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; border: 1px solid #bfdbfe; }
        .alert-message { background-color: #eff6ff; border-left: 4px solid #3b82f6; padding: 16px; border-radius: 0 8px 8px 0; margin-bottom: 24px; color: #1e40af; font-size: 14px; font-weight: 500; }
        
        @keyframes spin { to { transform: rotate(360deg); } }

        @media print {
            body * { visibility: hidden; }
            #area-imprimir, #area-imprimir * { visibility: visible; }
            #area-imprimir { position: absolute; left: 0; top: 0; width: 100%; border: none; box-shadow: none; }
        }
    </style>

    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div>
                <h2>Reporte de Postulantes</h2>
                <p>Monitoree las solicitudes de inscripción, filtre por opciones y descargue los listados en los formatos requeridos.</p>
            </div>
            
            <div wire:loading class="loading-badge">
                <svg class="view-icon" style="animation: spin 1s linear infinite;" viewBox="0 0 24 24"><path d="M21 12a9 9 0 11-6.219-8.56"/></svg>
                <span>Actualizando datos...</span>
            </div>
        </div>
    </div>

    @if (session()->has('mensaje'))
        <div class="alert-message">
            • {{ session('mensaje') }}
        </div>
    @endif

    <div class="card">
        <h2>Parámetros del Reporte</h2>

        <div class="form-grid">
            <div class="form-group">
                <label>Buscar postulante</label>
                <input type="text" wire:model.live.debounce.300ms="busqueda" placeholder="CI, nombre, correo o celular...">
            </div>

            <div class="form-group">
                <label>Estado Inscripción</label>
                <select wire:model.live="estado">
                    <option value="">Todos los estados</option>
                    <option value="pendiente">⏳ Pendiente</option>
                    <option value="aprobado">✅ Aprobado</option>
                    <option value="rechazado">❌ Rechazado</option>
                </select>
            </div>

            <div class="form-group">
                <label>Carrera de Interés</label>
                <select wire:model.live="carrera_id">
                    <option value="">Todas las carreras</option>
                    @foreach ($carreras as $carrera)
                        <option value="{{ $carrera->id }}">{{ $carrera->nombre }}</option>
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
                <button wire:click="listaAprobados" class="btn btn-secondary {{ $tipoLista === 'aprobados' ? 'btn-active' : '' }}">Aprobados</button>
                <button wire:click="listaReprobados" class="btn btn-secondary {{ $tipoLista === 'reprobados' ? 'btn-active' : '' }}">Rechazados</button>
                <button wire:click="limpiarFiltros" class="btn btn-danger-text">Limpiar filtros</button>
            </div>

            <div class="btn-group">
                <button wire:click="exportarLista('pdf')" class="btn btn-secondary">
                    <svg class="view-icon" viewBox="0 0 24 24"><path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-6a2 2 0 012-2h16a2 2 0 012 2v6a2 2 0 01-2 2h-2M6 14h12v8H6z"/></svg>
                    Imprimir
                </button>
                
                <button wire:click="exportarLista('word')" class="btn btn-word">
                    <svg class="view-icon" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                    Word
                </button>

                <button wire:click="exportarLista('csv')" class="btn btn-csv">
                    <svg class="view-icon" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M8 13h8M8 17h6"/></svg>
                    CSV
                </button>

                <button wire:click="exportarLista('excel')" class="btn btn-excel">
                    <svg class="view-icon" viewBox="0 0 24 24"><path d="M14 3v6h6M5 12h14M5 18h14M5 6h9M5 3h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2z"/></svg>
                    Excel
                </button>
            </div>
        </div>
    </div>

    <div class="card" id="area-imprimir">
        <div style="display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; padding-bottom: 16px; border-bottom: 1px solid #e2e8f0;">
            <div>
                <h2 style="margin: 0; font-size: 18px;">Lista Destinataria de Postulantes</h2>
                <p style="margin-top: 4px;">Filtros aplicados para un total de: <strong style="color: #2563eb;">{{ $totalPostulantes }}</strong> registros.</p>
            </div>
            <div style="font-size: 12px; background: #f1f5f9; padding: 6px 14px; border-radius: 6px; font-weight: 700; text-transform: uppercase; color: #475569;">
                Segmento: <span style="color: #2563eb;">{{ $tipoLista }}</span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th style="text-align: center; width: 60px;">Nro</th>
                        <th>Nombre completo</th>
                        <th>Documento Identidad</th>
                        <th>Medios de Contacto</th>
                        <th>Carreras Solicitadas (1° y 2° Opción)</th>
                        <th style="text-align: center;">Estado</th>
                        <th style="text-align: right;">Fecha Registro</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($postulantes as $index => $postulante)
                        <tr>
                            <td style="text-align: center; color: #94a3b8; font-weight: 600;">{{ $postulantes->firstItem() + $index }}</td>
                            <td><strong style="color: #0f172a;">{{ $postulante->user->name ?? '' }} {{ $postulante->user->apellido ?? '' }}</strong></td>
                            <td style="font-family: monospace; font-size: 13px; font-weight: 600;">{{ $postulante->user->ci ?? 'Sin CI' }}</td>
                            <td>
                                <div style="font-size: 13px; color: #1e293b;">{{ $postulante->user->email ?? 'Sin correo' }}</div>
                                <div style="font-size: 11px; color: #64748b; margin-top: 2px;">📞 {{ $postulante->user->telefono ?? 'Sin teléfono' }}</div>
                            </td>
                            <td>
                                <div style="font-size: 13px; font-weight: 500;"><span style="color:#94a3b8; font-size:11px; font-weight:bold;">1°</span> {{ $postulante->primeraOpcionCarrera->nombre ?? 'No asignada' }}</div>
                                <div style="font-size: 12px; color: #64748b; margin-top: 3px;"><span style="color:#94a3b8; font-size:11px;">2°</span> {{ $postulante->segundaOpcionCarrera->nombre ?? 'No asignada' }}</div>
                            </td>
                            <td style="text-align: center;">
                                @php
                                    $currentStatus = $postulante->estado_inscripcion ?? 'pendiente';
                                    $badgeStyle = match($currentStatus) {
                                        'aprobado' => 'badge-aprobado',
                                        'rechazado' => 'badge-rechazado',
                                        default => 'badge-pendiente'
                                    };
                                @endphp
                                <span class="badge {{ $badgeStyle }}">{{ $currentStatus }}</span>
                            </td>
                            <td style="text-align: right; color: #64748b; font-size: 13px; font-weight: 500;">
                                {{ $postulante->fecha_registro ? \Carbon\Carbon::parse($postulante->fecha_registro)->format('d/m/Y') : '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 48px; color: #94a3b8; font-size: 14px;">📁 Ningún registro coincide con los filtros establecidos.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top: 20px;">
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