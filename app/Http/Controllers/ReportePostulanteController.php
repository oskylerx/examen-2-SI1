<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Postulante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PDF;

class ReportePostulanteController extends Controller
{
    public function index()
    {
        return view('reporte.lista_general_postulantes');
    }
}