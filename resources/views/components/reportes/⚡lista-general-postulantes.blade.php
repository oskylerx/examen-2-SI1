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

    public function updatedBusqueda()
    {
        $this->resetPage();
    }

    public function updatedEstado()
    {
        $this->resetPage();
    }

    public function updatedCarreraId()
    {
        $this->resetPage();
    }

    public function updatedPorPagina()
    {
        $this->resetPage();
    }

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
        $this->busqueda = '';
        $this->estado = '';
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
        }

        if ($tipo === 'excel') {
            session()->flash('mensaje', 'La exportación a Excel se implementará después.');
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

<div>
    <div class="card">
        <h2>Reporte de Postulantes</h2>
        <p>
            Desde esta sección puede generar la lista general de postulantes,
            filtrar por carrera, estado o buscar por CI, nombre, apellido, teléfono o correo.
        </p>
    </div>

    @if (session()->has('mensaje'))
        <div class="card">
            <strong>{{ session('mensaje') }}</strong>
        </div>
    @endif

    <div class="card">
        <h2>Parámetros del reporte</h2>

        <div class="form-grid">
            <div>
                <label>Buscar postulante</label>
                <input
                    type="text"
                    wire:model.live="busqueda"
                    placeholder="CI, nombre, apellido, teléfono o correo"
                >
            </div>

            <div>
                <label>Estado</label>
                <select wire:model.live="estado">
                    <option value="">Todos</option>
                    <option value="pendiente">Pendiente</option>
                    <option value="aprobado">Aprobado</option>
                    <option value="rechazado">Rechazado</option>
                </select>
            </div>

            <div>
                <label>Carrera</label>
                <select wire:model.live="carrera_id">
                    <option value="">Todas las carreras</option>

                    @foreach ($carreras as $carrera)
                        <option value="{{ $carrera->id }}">
                            {{ $carrera->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label>Registros por página</label>
                <select wire:model.live="porPagina">
                    <option value="10">10 registros</option>
                    <option value="25">25 registros</option>
                    <option value="50">50 registros</option>
                    <option value="100">100 registros</option>
                </select>
            </div>
        </div>

        <div style="margin-top: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
            <button wire:click="generarListaPostulantes" class="btn-primary">
                Generar lista
            </button>

            <button wire:click="limpiarFiltros" class="btn-secondary">
                Limpiar filtros
            </button>

            <button wire:click="listaGeneral" class="btn-secondary">
                Lista general
            </button>

            <button wire:click="listaAprobados" class="btn-secondary">
                Lista aprobados
            </button>

            <button wire:click="listaReprobados" class="btn-secondary">
                Lista rechazados
            </button>

            <button wire:click="exportarLista('pdf')" class="btn-secondary">
                Imprimir lista
            </button>

            <button wire:click="exportarLista('excel')" class="btn-secondary">
                Exportar Excel
            </button>
        </div>
    </div>

    <div class="card" id="area-imprimir">
        <div style="display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap;">
            <div>
                <h2>Lista General de Postulantes</h2>
                <p>Total encontrados: <strong>{{ $totalPostulantes }}</strong></p>
            </div>

            <div>
                <strong>Tipo de lista:</strong>
                {{ ucfirst($tipoLista) }}
            </div>
        </div>

        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nro</th>
                        <th>Nombre completo</th>
                        <th>CI</th>
                        <th>Correo</th>
                        <th>Teléfono</th>
                        <th>Primera opción</th>
                        <th>Segunda opción</th>
                        <th>Estado</th>
                        <th>Fecha registro</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($postulantes as $index => $postulante)
                        <tr>
                            <td>{{ $postulantes->firstItem() + $index }}</td>

                            <td>
                                {{ $postulante->user->name ?? '' }}
                                {{ $postulante->user->apellido ?? '' }}
                            </td>

                            <td>
                                {{ $postulante->user->ci ?? 'Sin CI' }}
                            </td>

                            <td>
                                {{ $postulante->user->email ?? 'Sin correo' }}
                            </td>

                            <td>
                                {{ $postulante->user->telefono ?? 'Sin teléfono' }}
                            </td>

                            <td>
                                {{ $postulante->primeraOpcionCarrera->nombre ?? 'No asignada' }}
                            </td>

                            <td>
                                {{ $postulante->segundaOpcionCarrera->nombre ?? 'No asignada' }}
                            </td>

                            <td>
                                <span class="badge">
                                    {{ ucfirst($postulante->estado_inscripcion ?? 'pendiente') }}
                                </span>
                            </td>

                            <td>
                                {{ $postulante->fecha_registro ? \Carbon\Carbon::parse($postulante->fecha_registro)->format('d/m/Y') : '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="text-align: center;">
                                No se encontraron postulantes.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top: 20px;">
            {{ $postulantes->links() }}
        </div>
    </div>

    <script>
        window.addEventListener('imprimir-lista', () => {
            window.print();
        });
    </script>
</div>