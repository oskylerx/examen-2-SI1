@extends('layouts.app')

@section('title', 'Dashboard Postulante | Sistema CUP')

@section('page-title', 'Panel del Postulante')

@section('content')
    <div class="grid">
        <div class="stat">
            <span>Costo CUP</span>
            <strong>700 Bs</strong>
        </div>

        <div class="stat">
            <span>Materias</span>
            <strong>4</strong>
        </div>

        <div class="stat">
            <span>Pruebas por materia</span>
            <strong>3</strong>
        </div>

        <div class="stat">
            <span>Nota mínima</span>
            <strong>60</strong>
        </div>
    </div>

    <div class="card">
        <h2>Funciones principales</h2>
        <p>Desde este panel el postulante podrá revisar su inscripción, documentos, pago, materias, notas y resultado final.</p>
    </div>

    <div class="card">
        <h2>Requisitos del CUP</h2>
        <p>Título de Bachiller, Cédula de Identidad, Boletín de 6to de Secundaria y Comprobante de pago.</p>
    </div>
@endsection