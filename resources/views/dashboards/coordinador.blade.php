@extends('layouts.app')

@section('title', 'Dashboard Coordinador | Sistema CUP')

@section('page-title', 'Panel del Coordinador')

@section('content')
    <div class="grid">
        <div class="stat">
            <span>Grupos asignados</span>
            <strong>0</strong>
        </div>

        <div class="stat">
            <span>Materias activas</span>
            <strong>4</strong>
        </div>

        <div class="stat">
            <span>Docentes asignados</span>
            <strong>0</strong>
        </div>

        <div class="stat">
            <span>Reportes académicos</span>
            <strong>8</strong>
        </div>
    </div>

    <div class="card">
        <h2>Funciones principales</h2>
        <p>Desde este panel el coordinador podrá revisar grupos, asignaciones docentes, materias, horarios y reportes académicos.</p>
    </div>

    <div class="card">
        <h2>Casos de uso relacionados</h2>
        <p>CU05, CU06, CU07, CU09, CU10, CU11, CU12, CU13 y CU14.</p>
    </div>
@endsection