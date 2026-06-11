<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Grupo extends Model
{
    protected $table = 'grupo';

    protected $fillable = [
        'docente_id',
        'coordinador_id',
        'carrera_id',
        'nombre',
        'gestion',
        'cupos_maximo',
        'activo',
    ];

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class, 'docente_id');
    }

    public function coordinador(): BelongsTo
    {
        return $this->belongsTo(Coordinador::class, 'coordinador_id');
    }

    public function carrera(): BelongsTo
    {
        return $this->belongsTo(Carrera::class, 'carrera_id');
    }

    public function postulantes(): HasMany
    {
        return $this->hasMany(Postulante::class, 'grupo_id');
    }

    public function reportes(): HasMany
    {
        return $this->hasMany(Reporte::class, 'grupo_id');
    }

    public function asignacionesAcademicas(): HasMany
    {
        return $this->hasMany(AsignacionAcademica::class, 'grupo_id');
    }
}
