@extends('layouts.app')

@section('title', 'Dashboard Docente | Sistema CUP')

@section('page-title', 'Panel del Docente')

@section('content')
    <div class="grid">
        <div class="stat">
            <span>Grupos asignados</span>
            <strong>0</strong>
        </div>

        <div class="stat">
            <span>Materias asignadas</span>
            <strong>0</strong>
        </div>

        <div class="stat">
            <span>Pruebas por materia</span>
            <strong>3</strong>
        </div>

        <div class="stat">
            <span>Mínimo de aprobación</span>
            <strong>60</strong>
        </div>
    </div>

    <div class="card">
        <h2>Funciones principales</h2>
        <p>Desde este panel el docente podrá registrar las 3 notas por materia y consultar los grupos que tiene asignados.</p>
    </div>

    <div class="card">
        <h2>Casos de uso relacionados</h2>
        <p>CU08 Registrar 3 Notas por Materia.</p>
    </div>
@endsection