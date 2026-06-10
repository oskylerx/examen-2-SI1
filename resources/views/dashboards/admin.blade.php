@extends('layouts.app')

@section('title', 'Dashboard Administrador | Sistema CUP')

@section('page-title', 'Panel del Administrador')

@section('content')
    <div class="grid">
        <div class="stat">
            <span>Postulantes registrados</span>
            <strong>0</strong>
        </div>

        <div class="stat">
            <span>Docentes registrados</span>
            <strong>0</strong>
        </div>

        <div class="stat">
            <span>Grupos habilitados</span>
            <strong>0</strong>
        </div>

        <div class="stat">
            <span>Reportes disponibles</span>
            <strong>9</strong>
        </div>
    </div>

    <div class="card">
        <h2>Funciones principales</h2>
        <p>Desde este panel el administrador podrá gestionar postulantes, docentes, asignaciones académicas y reportes generales del CUP.</p>
    </div>

    <div class="card">
        <h2>Casos de uso relacionados</h2>
        <p>CU03, CU04, CU05, CU06, CU07, CU09, CU10, CU11, CU12, CU13 y CU14.</p>
    </div>
@endsection