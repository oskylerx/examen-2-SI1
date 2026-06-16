<?php

namespace App\Http\Controllers;

class DocenteGrupoController extends Controller
{
    public function index()
    {
        return view('docente.mis-grupos');
    }
}