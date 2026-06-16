<?php

namespace App\Http\Controllers;

class EstadisticaCalificacionController extends Controller
{
    public function index()
    {
        return view('admin.estadisticas-calificaciones');
    }
}