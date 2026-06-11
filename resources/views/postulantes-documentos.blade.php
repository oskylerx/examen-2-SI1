@extends('layouts.app')

@section('content')
<style>
    .doc-container {
        max-width: 1100px;
        margin: 0 auto;
        padding: 20px;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .doc-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    }

    .doc-title {
        font-size: 26px;
        color: #2c3e50;
        margin-bottom: 15px;
    }

    .doc-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: 10px;
    }

    .doc-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
    }

    .doc-table th {
        background: #f1f5f9;
        padding: 12px;
        text-align: left;
        border-bottom: 2px solid #cbd5e1;
    }

    .doc-table td {
        padding: 12px;
        border-bottom: 1px solid #e2e8f0;
        vertical-align: top;
    }

    .btn {
        padding: 8px 14px;
        border-radius: 6px;
        text-decoration: none;
        border: none;
        cursor: pointer;
        font-weight: 600;
        font-size: 13px;
        display: inline-block;
    }

    .btn-back { background: #64748b; color: #fff; }
    .btn-file { background: #2563eb; color: #fff; }
    .btn-save { background: #16a085; color: #fff; }

    .badge {
        padding: 5px 8px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        display: inline-block;
    }

    .badge-pendiente { background: #fef3c7; color: #92400e; }
    .badge-validado { background: #dcfce7; color: #166534; }
    .badge-observado { background: #e0f2fe; color: #075985; }
    .badge-rechazado { background: #fee2e2; color: #991b1b; }

    .alert {
        padding: 12px 16px;
        border-radius: 6px;
        margin-bottom: 20px;
        font-size: 14px;
    }

    .alert-success {
        background-color: #d1e7dd;
        color: #0f5132;
        border: 1px solid #badbcc;
    }

    .alert-danger {
        background-color: #f8d7da;
        color: #842029;
        border: 1px solid #f5c2c7;
    }

    textarea, select {
        width: 100%;
        padding: 8px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        margin-bottom: 8px;
    }
</style>

<div class="doc-container">

    <h1 class="doc-title">Revisión de documentos</h1>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Corrige los siguientes errores:</strong>
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="doc-card">
        <h2>Datos del postulante</h2>

        <div class="doc-grid">
            <p><strong>Nombre:</strong> {{ $postulante->user->name }} {{ $postulante->user->apellido }}</p>
            <p><strong>CI:</strong> {{ $postulante->user->ci }}</p>
            <p><strong>Correo:</strong> {{ $postulante->user->email }}</p>
            <p><strong>Usuario:</strong> {{ $postulante->user->username }}</p>
            <p><strong>Estado inscripción:</strong> {{ $postulante->estado_inscripcion }}</p>
            <p><strong>Estado usuario:</strong> {{ $postulante->user->estado }}</p>
            <p><strong>Primera opción:</strong> {{ $postulante->primeraOpcionCarrera->nombre ?? 'No registrada' }}</p>
            <p><strong>Segunda opción:</strong> {{ $postulante->segundaOpcionCarrera->nombre ?? 'No registrada' }}</p>
        </div>

        <a href="{{ route('postulantes.index') }}" class="btn btn-back">
            Volver
        </a>
    </div>

    <div class="doc-card">
        <h2>Documentos subidos</h2>

        <p>
            Cuando los documentos requeridos estén en estado <strong>validado</strong>,
            el sistema cambiará automáticamente la inscripción a <strong>aceptado</strong>,
            activará el usuario y enviará las credenciales por correo.
        </p>

        <table class="doc-table">
            <thead>
                <tr>
                    <th>Documento</th>
                    <th>Tipo</th>
                    <th>Archivo</th>
                    <th>Estado</th>
                    <th>Fecha subida</th>
                    <th>Revisión</th>
                </tr>
            </thead>
            <tbody>
                @forelse($postulante->documentos as $documento)
                    <tr>
                        <td>{{ $documento->nombre }}</td>
                        <td>{{ $documento->tipo }}</td>
                        <td>
                            @if($documento->archivo)
                                <a href="{{ asset('storage/' . $documento->archivo) }}" target="_blank" class="btn btn-file">
                                    Ver archivo
                                </a>
                            @else
                                Sin archivo
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-{{ $documento->estado }}">
                                {{ $documento->estado }}
                            </span>
                        </td>
                        <td>{{ $documento->fecha_subida ?? 'No registrada' }}</td>
                        <td>
                            <form action="{{ route('postulantes.documentos.update', [$postulante, $documento]) }}" method="POST">
                                @csrf
                                @method('PATCH')

                                <select name="estado" required>
                                    <option value="pendiente" {{ $documento->estado === 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                                    <option value="validado" {{ $documento->estado === 'validado' ? 'selected' : '' }}>Validado</option>
                                    <option value="observado" {{ $documento->estado === 'observado' ? 'selected' : '' }}>Observado</option>
                                    <option value="rechazado" {{ $documento->estado === 'rechazado' ? 'selected' : '' }}>Rechazado</option>
                                </select>

                                <textarea name="otro" rows="2" placeholder="Observación opcional">{{ $documento->otro }}</textarea>

                                <button type="submit" class="btn btn-save">
                                    Guardar revisión
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            Este postulante todavía no tiene documentos registrados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
@endsection