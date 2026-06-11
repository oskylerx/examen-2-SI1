<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PostulanteController;
use Illuminate\Support\Facades\Route;


Route::get('/', fn () => redirect()->route('login'));

// //////////////////////////////////////////////////////////////////////////

Route::get('/login', [AuthController::class, 'mostrarLogin'])
    ->middleware('guest')
    ->name('login');

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('guest')
    ->name('login.post');

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::get('/dashboard', [AuthController::class, 'dashboard'])
    ->middleware('auth')
    ->name('dashboard');

// //////////////////////   ADMINISTRADOR    ///////////////////////////////////////////////////
Route::middleware(['auth', 'role:Administrador'])->group(function () {
    Route::get('/dashboard/admin', function () {
        return view('dashboards.admin');
    })->name('dashboard.admin');
    Route::resource('postulantes', PostulanteController::class);// CU03 Gestionar Postulante
    Route::patch('/postulantes/{id}/estado', [PostulanteController::class, 'updateEstado'])->name('postulantes.updateEstado');
    // CU04 Gestionar Docente
    // CU05 Asignar Docente a Grupo y Materia
    // CU06 Reporte Cantidad de Grupos
    // CU07 Lista General de Postulantes
});

// //////////////////////   COORDINADOR    ///////////////////////////////////////////////////

Route::middleware(['auth', 'role:Coordinador'])->group(function () {
    Route::get('/dashboard/coordinador', function () {
        return view('dashboards.coordinador');
    })->name('dashboard.coordinador');

    // CU05 Asignar Docente a Grupo y Materia
    // CU06 Reporte Cantidad de Grupos
    // CU09 Reporte Promedios Generales
    // CU10 Lista Aprobados
    // CU11 Lista Reprobados
    // CU12 Estadísticas por Materia
    // CU13 Reporte Docentes por Grupo
    // CU14 Reporte Grupos con Mayor Aprobación
});

// //////////////////////   DOCENTE    ///////////////////////////////////////////////////

Route::middleware(['auth', 'role:Docente'])->group(function () {
    Route::get('/dashboard/docente', function () {
        return view('dashboards.docente');
    })->name('dashboard.docente');

    // CU08 Registrar 3 Notas por Materia
});

// //////////////////////   POSTULANTE    ///////////////////////////////////////////////////

Route::middleware(['auth', 'role:Postulante'])->group(function () {
    Route::get('/dashboard/postulante', function () {
        return view('dashboards.postulante');
    })->name('dashboard.postulante');

    // CU03 Gestionar Postulante, limitado a su propio registro
});
