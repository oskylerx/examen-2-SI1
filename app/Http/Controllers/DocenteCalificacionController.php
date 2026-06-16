<?php

namespace App\Http\Controllers;

class DocenteCalificacionController extends Controller
{
    public function index()
    {
        return view('docente.calificaciones');
    }
}