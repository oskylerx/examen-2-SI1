<?php

namespace App\Http\Controllers;

class NotaDocenteController extends Controller
{
    public function index()
    {
        return view('docente.notas');
    }
}