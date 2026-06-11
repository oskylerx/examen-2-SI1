<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsignacionCupo extends Model
{
    protected $table = 'asignacion_cupo';

    protected $fillable = [
        'postulante_id',
        'carrera_id',
        'fecha_asignacion',
        'promedio_final',
        'posicion_ranking',
        'estado',
    ];

    public function postulante(): BelongsTo
    {
        return $this->belongsTo(Postulante::class, 'postulante_id');
    }

    public function carrera(): BelongsTo
    {
        return $this->belongsTo(Carrera::class, 'carrera_id');
    }
}