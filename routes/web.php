<?php

use App\Http\Controllers\AsignacionAcademicaController;
use App\Http\Controllers\AsignacionCupoController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DocenteCalificacionController;
use App\Http\Controllers\DocenteController;
use App\Http\Controllers\DocenteGrupoController;
use App\Http\Controllers\DocenteResultadoController;
use App\Http\Controllers\EstadisticaCalificacionController;
use App\Http\Controllers\GrupoController;
use App\Http\Controllers\NotaDocenteController;
use App\Http\Controllers\PostulanteController;
use App\Http\Controllers\ReportePostulanteController;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

Route::get('/probar-correo', function () {
    Mail::raw('Correo de prueba desde Laravel CUP', function ($message) {
        $message->to('oscarcrodri3@gmail.com')
            ->subject('Prueba de correo CUP');
    });

    return 'Correo enviado correctamente';
});

Route::get('/', fn () => redirect()->route('preinscripcion'));

Route::get('/preinscripcion', function () {
    return view('preinscripcion');
})->name('preinscripcion');

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

    Route::get('postulantes/{postulante}/documentos', [PostulanteController::class, 'documentos'])
        ->name('postulantes.documentos');

    Route::patch('postulantes/{postulante}/documentos/{documento}', [PostulanteController::class, 'actualizarDocumento'])
        ->name('postulantes.documentos.update');

    Route::patch('postulantes/{postulante}/estado', [PostulanteController::class, 'updateEstado'])
        ->name('postulantes.updateEstado');
    Route::resource('postulantes', PostulanteController::class); // CU03 Gestionar Postulante
    Route::patch('/postulantes/{id}/estado', [PostulanteController::class, 'updateEstado'])->name('postulantes.updateEstado');
    // CU04 Gestionar Docente

    Route::get('/admin/grupos', [GrupoController::class, 'index'])
        ->name('admin.grupos'); // CU06 Reporte Cantidad de Grupos
    // CU07 Lista General de Postulantes

});
// //////////////////////////////////////////////////////////////////////////
Route::middleware(['auth', 'role:Administrador,Coordinador'])->group(function () {
    Route::get('/docentes', [DocenteController::class, 'index'])
        ->name('docentes.index');
    Route::get('/admin/asignacion-cupos', [AsignacionCupoController::class, 'index'])
        ->name('admin.asignacion-cupos');
    Route::get('/admin/asignacion-academica', [AsignacionAcademicaController::class, 'index'])
        ->name('admin.asignacion-academica'); // CU05 Asignar Docente a Grupo y Materia
    Route::get('/reportes/postulantes/lista-general', [ReportePostulanteController::class, 'index'])
        ->middleware('auth')
        ->name('reportes.postulantes.lista-general');
    Route::get('/admin/estadisticas-calificaciones', [EstadisticaCalificacionController::class, 'index'])
        ->name('admin.estadisticas-calificaciones');
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

    Route::get('/docente/notas', [NotaDocenteController::class, 'index'])
        ->name('docente.notas'); // CU08 Registrar 3 Notas por Materia
    Route::get('/docente/mis-grupos', [DocenteGrupoController::class, 'index'])
        ->name('docente.mis-grupos');
    Route::get('/docente/calificaciones', [DocenteCalificacionController::class, 'index'])
        ->name('docente.calificaciones');
    Route::get('/docente/resultados', [DocenteResultadoController::class, 'index'])
        ->name('docente.resultados');
});

// //////////////////////   POSTULANTE    ///////////////////////////////////////////////////

Route::middleware(['auth', 'role:Postulante'])->group(function () {
    Route::get('/dashboard/postulante', function () {
        return view('dashboards.postulante');
    })->name('dashboard.postulante');

    // CU03 Gestionar Postulante, limitado a su propio registro
});
