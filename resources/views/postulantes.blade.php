@extends('layouts.app')

@section('content')
    <style>
        /* Estilos Generales y Contenedor */
        .gestion-container {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .main-title {
            color: #2c3e50;
            font-size: 28px;
            margin-bottom: 20px;
            border-bottom: 2px solid #ecf0f1;
            padding-bottom: 10px;
        }

        .section-title {
            color: #34495e;
            font-size: 20px;
            margin-bottom: 15px;
        }

        .subsection-title {
            color: #7f8c8d;
            font-size: 16px;
            text-transform: uppercase;
            margin-top: 20px;
            margin-bottom: 10px;
            letter-spacing: 0.5px;
        }

        /* Mensajes de Alerta */
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

        .alert ul {
            margin: 5px 0 0 20px;
            padding: 0;
        }

        /* Navegación de Acciones Superiores */
        .nav-actions {
            margin-bottom: 25px;
            background: #f8f9fa;
            padding: 12px;
            border-radius: 6px;
            border: 1px solid #e9ecef;
        }

        .nav-actions a {
            color: #3498db;
            text-decoration: none;
            font-weight: 600;
            margin: 0 10px;
        }

        .nav-actions a:hover {
            text-decoration: underline;
        }

        /* Paneles / Formularios Dinámicos */
        .form-panel {
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
            padding: 25px;
            margin-bottom: 30px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group-full {
            grid-column: 1 / -1;
        }

        .form-group label {
            font-size: 13px;
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 6px;
        }

        .form-control,
        select,
        textarea {
            padding: 8px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 14px;
            background-color: #fff;
            color: #334155;
            transition: border-color 0.2s;
        }

        .form-control:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #3498db;
        }

        /* Botones */
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: background 0.2s;
        }

        .btn-primary {
            background: #3498db;
            color: #fff;
        }

        .btn-primary:hover {
            background: #2980b9;
        }

        .btn-action {
            padding: 4px 8px;
            font-size: 12px;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
        }

        .btn-view {
            background: #5c6bc0;
            color: white;
        }

        .btn-edit {
            background: #f39c12;
            color: white;
        }

        .btn-delete {
            background: #e74c3c;
            color: white;
        }

        /* Tabla */
        .table-container {
            overflow-x: auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }

        .custom-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            text-align: left;
        }

        .custom-table th {
            background-color: #f1f5f9;
            color: #475569;
            padding: 12px;
            font-weight: 600;
            border-bottom: 2px solid #cbd5e1;
        }

        .custom-table td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
            color: #334155;
            vertical-align: middle;
        }

        .custom-table tr:hover {
            background-color: #f8fafc;
        }

        /* Select del estado en la tabla */
        .select-status-inline {
            padding: 4px 8px;
            font-size: 13px;
            font-weight: 500;
            border-radius: 4px;
            border: 1px solid #cbd5e1;
            cursor: pointer;
        }

        /* Colores decorativos de estado basados en la selección */
        .status-pendiente {
            background-color: #fef3c7;
            color: #d97706;
            border-color: #fcd34d;
        }

        .status-observado {
            background-color: #e0f2fe;
            color: #0369a1;
            border-color: #7dd3fc;
        }

        .status-aceptado {
            background-color: #d1e7dd;
            color: #0f5132;
            border-color: #badbcc;
        }

        .status-rechazado {
            background-color: #f8d7da;
            color: #842029;
            border-color: #f5c2c7;
        }

        .btn-docs {
            background: #16a085;
            color: white;
        }
    </style>

    <div class="gestion-container">

        <h1 class="main-title">CU03 - Gestionar Postulante</h1>

        {{-- Notificaciones --}}
        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <strong>Corrige los siguientes errores:</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Navegación --}}
        <div class="nav-actions">
            <a href="{{ route('postulantes.index') }}">Lista</a> |
            <a href="{{ route('postulantes.create') }}">Nuevo postulante</a>
        </div>

        {{-- Sección Crear --}}
        @if (isset($modo) && $modo === 'crear')
            <div class="form-panel">
                <h2 class="section-title">Registrar Postulante</h2>

                <form action="{{ route('postulantes.store') }}" method="POST">
                    @csrf

                    <h3 class="subsection-title">Datos personales</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>CI:</label>
                            <input type="text" name="ci" class="form-control" value="{{ old('ci') }}" required>
                        </div>
                        <div class="form-group">
                            <label>Nombre:</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                        </div>
                        <div class="form-group">
                            <label>Apellido:</label>
                            <input type="text" name="apellido" class="form-control" value="{{ old('apellido') }}"
                                required>
                        </div>
                        <div class="form-group">
                            <label>Teléfono:</label>
                            <input type="text" name="telefono" class="form-control" value="{{ old('telefono') }}">
                        </div>
                        <div class="form-group">
                            <label>Correo:</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
                        </div>
                        <div class="form-group">
                            <label>Fecha de nacimiento:</label>
                            <input type="date" name="fecha_nacimiento" class="form-control"
                                value="{{ old('fecha_nacimiento') }}">
                        </div>
                        <div class="form-group">
                            <label>Género:</label>
                            <select name="genero">
                                <option value="">Seleccione...</option>
                                <option value="masculino" {{ old('genero') === 'masculino' ? 'selected' : '' }}>Masculino
                                </option>
                                <option value="femenino" {{ old('genero') === 'femenino' ? 'selected' : '' }}>Femenino
                                </option>
                                <option value="otro" {{ old('genero') === 'otro' ? 'selected' : '' }}>Otro</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Dirección:</label>
                            <input type="text" name="direccion" class="form-control" value="{{ old('direccion') }}">
                        </div>
                        <div class="form-group">
                            <label>Colegio:</label>
                            <input type="text" name="colegio" class="form-control" value="{{ old('colegio') }}">
                        </div>
                        <div class="form-group">
                            <label>Ciudad:</label>
                            <input type="text" name="ciudad" class="form-control" value="{{ old('ciudad') }}">
                        </div>
                    </div>

                    <h3 class="subsection-title">Carreras</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Primera opción:</label>
                            <select name="primera_opcion_carrera_id" required>
                                <option value="">Seleccione...</option>
                                @foreach ($carreras as $carrera)
                                    <option value="{{ $carrera->id }}"
                                        {{ old('primera_opcion_carrera_id') == $carrera->id ? 'selected' : '' }}>
                                        {{ $carrera->codigo_carrera }} - {{ $carrera->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Segunda opción:</label>
                            <select name="segunda_opcion_carrera_id" required>
                                <option value="">Seleccione...</option>
                                @foreach ($carreras as $carrera)
                                    <option value="{{ $carrera->id }}"
                                        {{ old('segunda_opcion_carrera_id') == $carrera->id ? 'selected' : '' }}>
                                        {{ $carrera->codigo_carrera }} - {{ $carrera->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" style="margin-top: 15px;">Guardar postulante</button>
                </form>
            </div>
        @endif

        {{-- Sección Editar --}}
        @if (isset($modo) && $modo === 'editar')
            <div class="form-panel">
                <h2 class="section-title">Editar Postulante</h2>

                <form action="{{ route('postulantes.update', $postulante) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <h3 class="subsection-title">Datos de usuario</h3>
                    <div
                        style="background: #f8fafc; padding: 12px; border-radius: 6px; margin-bottom: 15px; font-size: 14px;">
                        <span style="margin-right: 20px;"><strong>Username:</strong>
                            {{ $postulante->user->username }}</span>
                        <span><strong>Estado usuario:</strong> {{ $postulante->user->estado }}</span>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label>CI:</label>
                            <input type="text" name="ci" class="form-control"
                                value="{{ old('ci', $postulante->user->ci) }}" required>
                        </div>
                        <div class="form-group">
                            <label>Nombre:</label>
                            <input type="text" name="name" class="form-control"
                                value="{{ old('name', $postulante->user->name) }}" required>
                        </div>
                        <div class="form-group">
                            <label>Apellido:</label>
                            <input type="text" name="apellido" class="form-control"
                                value="{{ old('apellido', $postulante->user->apellido) }}" required>
                        </div>
                        <div class="form-group">
                            <label>Teléfono:</label>
                            <input type="text" name="telefono" class="form-control"
                                value="{{ old('telefono', $postulante->user->telefono) }}">
                        </div>
                        <div class="form-group">
                            <label>Correo:</label>
                            <input type="email" name="email" class="form-control"
                                value="{{ old('email', $postulante->user->email) }}" required>
                        </div>
                    </div>

                    <h3 class="subsection-title">Datos de inscripción</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Fecha de nacimiento:</label>
                            <input type="date" name="fecha_nacimiento" class="form-control"
                                value="{{ old('fecha_nacimiento', $postulante->fecha_nacimiento) }}">
                        </div>
                        <div class="form-group">
                            <label>Género:</label>
                            <select name="genero">
                                <option value="">Seleccione...</option>
                                <option value="masculino"
                                    {{ old('genero', $postulante->genero) === 'masculino' ? 'selected' : '' }}>Masculino
                                </option>
                                <option value="femenino"
                                    {{ old('genero', $postulante->genero) === 'femenino' ? 'selected' : '' }}>Femenino
                                </option>
                                <option value="otro"
                                    {{ old('genero', $postulante->genero) === 'otro' ? 'selected' : '' }}>Otro</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Dirección:</label>
                            <input type="text" name="direccion" class="form-control"
                                value="{{ old('direccion', $postulante->direccion) }}">
                        </div>
                        <div class="form-group">
                            <label>Colegio:</label>
                            <input type="text" name="colegio" class="form-control"
                                value="{{ old('colegio', $postulante->colegio) }}">
                        </div>
                        <div class="form-group">
                            <label>Ciudad:</label>
                            <input type="text" name="ciudad" class="form-control"
                                value="{{ old('ciudad', $postulante->ciudad) }}">
                        </div>
                        <div class="form-group">
                            <label>Primera opción:</label>
                            <select name="primera_opcion_carrera_id" required>
                                <option value="">Seleccione...</option>
                                @foreach ($carreras as $carrera)
                                    <option value="{{ $carrera->id }}"
                                        {{ old('primera_opcion_carrera_id', $postulante->primera_opcion_carrera_id) == $carrera->id ? 'selected' : '' }}>
                                        {{ $carrera->codigo_carrera }} - {{ $carrera->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Segunda opción:</label>
                            <select name="segunda_opcion_carrera_id" required>
                                <option value="">Seleccione...</option>
                                @foreach ($carreras as $carrera)
                                    <option value="{{ $carrera->id }}"
                                        {{ old('segunda_opcion_carrera_id', $postulante->segunda_opcion_carrera_id) == $carrera->id ? 'selected' : '' }}>
                                        {{ $carrera->codigo_carrera }} - {{ $carrera->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Grupo:</label>
                            <select name="grupo_id">
                                <option value="">Sin grupo asignado</option>
                                @foreach ($grupos as $grupo)
                                    <option value="{{ $grupo->id }}"
                                        {{ old('grupo_id', $postulante->grupo_id) == $grupo->id ? 'selected' : '' }}>
                                        {{ $grupo->nombre }} - {{ $grupo->gestion }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Estado de inscripción:</label>
                            <select name="estado_inscripcion" required>
                                <option value="pendiente"
                                    {{ old('estado_inscripcion', $postulante->estado_inscripcion) === 'pendiente' ? 'selected' : '' }}>
                                    Pendiente</option>
                                <option value="observado"
                                    {{ old('estado_inscripcion', $postulante->estado_inscripcion) === 'observado' ? 'selected' : '' }}>
                                    Observado</option>
                                <option value="aceptado"
                                    {{ old('estado_inscripcion', $postulante->estado_inscripcion) === 'aceptado' ? 'selected' : '' }}>
                                    Aceptado</option>
                                <option value="rechazado"
                                    {{ old('estado_inscripcion', $postulante->estado_inscripcion) === 'rechazado' ? 'selected' : '' }}>
                                    Rechazado</option>
                            </select>
                        </div>
                        <div class="form-group form-group-full">
                            <label>Observación:</label>
                            <textarea name="observacion" rows="2">{{ old('observacion', $postulante->observacion) }}</textarea>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Actualizar postulante</button>
                </form>
            </div>
        @endif

        {{-- Sección Detalle --}}
        @if (isset($modo) && $modo === 'detalle')
            <div class="form-panel" style="line-height: 1.6;">
                <h2 class="section-title">Detalle del Postulante</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 10px;">
                    <p><strong>ID:</strong> {{ $postulante->id }}</p>
                    <p><strong>Username:</strong> {{ $postulante->user->username }}</p>
                    <p><strong>Estado usuario:</strong> {{ $postulante->user->estado }}</p>
                    <p><strong>CI:</strong> {{ $postulante->user->ci }}</p>
                    <p><strong>Nombre:</strong> {{ $postulante->user->name }} {{ $postulante->user->apellido }}</p>
                    <p><strong>Teléfono:</strong> {{ $postulante->user->telefono ?? 'Sin teléfono' }}</p>
                    <p><strong>Correo:</strong> {{ $postulante->user->email ?? 'Sin correo' }}</p>
                    <p><strong>Fecha de nacimiento:</strong> {{ $postulante->fecha_nacimiento ?? 'No registrado' }}</p>
                    <p><strong>Género:</strong> {{ $postulante->genero ?? 'No registrado' }}</p>
                    <p><strong>Dirección:</strong> {{ $postulante->direccion ?? 'No registrado' }}</p>
                    <p><strong>Colegio:</strong> {{ $postulante->colegio ?? 'No registrado' }}</p>
                    <p><strong>Ciudad:</strong> {{ $postulante->ciudad ?? 'No registrado' }}</p>
                    <p><strong>Fecha registro:</strong> {{ $postulante->fecha_registro ?? 'No registrado' }}</p>
                    <p><strong>Primera opción:</strong> {{ $postulante->primeraOpcionCarrera->codigo_carrera ?? '' }} -
                        {{ $postulante->primeraOpcionCarrera->nombre ?? 'No registrado' }}</p>
                    <p><strong>Segunda opción:</strong> {{ $postulante->segundaOpcionCarrera->codigo_carrera ?? '' }} -
                        {{ $postulante->segundaOpcionCarrera->nombre ?? 'No registrado' }}</p>
                    <p><strong>Grupo:</strong>
                        {{ $postulante->grupo ? $postulante->grupo->nombre . ' - ' . $postulante->grupo->gestion : 'Sin grupo asignado' }}
                    </p>
                    <p><strong>Estado inscripción:</strong> {{ $postulante->estado_inscripcion }}</p>
                    <p><strong>Observación:</strong> {{ $postulante->observacion ?? 'Sin observación' }}</p>
                </div>
                <br>
                <a href="{{ route('postulantes.edit', $postulante) }}" class="btn btn-action btn-edit">Editar Datos</a>
            </div>
        @endif

        {{-- Tabla Principal --}}
        <h2 class="section-title" style="margin-top: 30px;">Lista de Postulantes</h2>

        <div class="table-container">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Nombre completo</th>
                        <th>CI</th>
                        <th>Correo</th>
                        <th>Primera opción</th>
                        <th>Segunda opción</th>
                        <th>Grupo</th>
                        <th style="min-width: 140px;">Estado inscripción</th>
                        <th>Estado usuario</th>
                        <th style="min-width: 150px;">Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($postulantes as $item)
                        <tr>
                            <td>{{ $item->id }}</td>
                            <td>{{ $item->user->username ?? '' }}</td>
                            <td>{{ $item->user->name ?? '' }} {{ $item->user->apellido ?? '' }}</td>
                            <td>{{ $item->user->ci ?? '' }}</td>
                            <td>{{ $item->user->email ?? '' }}</td>
                            <td>
                                <small>{{ $item->primeraOpcionCarrera->codigo_carrera ?? '' }} -
                                    {{ $item->primeraOpcionCarrera->nombre ?? '' }}</small>
                            </td>
                            <td>
                                <small>{{ $item->segundaOpcionCarrera->codigo_carrera ?? '' }} -
                                    {{ $item->segundaOpcionCarrera->nombre ?? '' }}</small>
                            </td>
                            <td>
                                @if ($item->grupo)
                                    {{ $item->grupo->nombre }}
                                @else
                                    <span style="color:#94a3b8; font-style:italic;">Sin asignar</span>
                                @endif
                            </td>

                            {{-- ACTUALIZACIÓN DIRECTA DE ESTADO --}}
                            <td>
                                <form action="{{ route('postulantes.updateEstado', $item->id) }}" method="POST"
                                    style="margin:0;">
                                    @csrf
                                    @method('PATCH')
                                    <select name="estado_inscripcion"
                                        class="select-status-inline status-{{ $item->estado_inscripcion }}"
                                        onchange="this.form.submit()">
                                        <option value="pendiente"
                                            {{ $item->estado_inscripcion === 'pendiente' ? 'selected' : '' }}>Pendiente
                                        </option>
                                        <option value="observado"
                                            {{ $item->estado_inscripcion === 'observado' ? 'selected' : '' }}>Observado
                                        </option>
                                        <option value="aceptado"
                                            {{ $item->estado_inscripcion === 'aceptado' ? 'selected' : '' }}>Aceptado
                                        </option>
                                        <option value="rechazado"
                                            {{ $item->estado_inscripcion === 'rechazado' ? 'selected' : '' }}>Rechazado
                                        </option>
                                    </select>
                                </form>
                            </td>

                            <td>{{ $item->user->estado ?? '' }}</td>

                            <td>
                                <a href="{{ route('postulantes.show', $item) }}" class="btn-action btn-view">Ver</a>

                                <a href="{{ route('postulantes.edit', $item) }}" class="btn-action btn-edit">Editar</a>

                                <a href="{{ route('postulantes.documentos', $item) }}" class="btn-action btn-docs">
                                    Documentos
                                </a>
                                <form action="{{ route('postulantes.destroy', $item) }}" method="POST"
                                    style="display:inline; margin:0;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-action btn-delete"
                                        style="border:none; cursor:pointer;"
                                        onclick="return confirm('¿Seguro que desea eliminar este postulante?')">
                                        Eliminar
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" style="text-align: center; color: #94a3b8; padding: 20px;">No hay
                                postulantes registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
@endsection
