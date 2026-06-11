<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Materia extends Model
{
    protected $table = 'materia';

    protected $fillable = [
        'nombre',
        'porcentaje_p1',
        'porcentaje_p2',
        'porcentaje_ef',
        'nota_min_aprob',
        'activo',
    ];

    public function examenes(): HasMany
    {
        return $this->hasMany(Examen::class, 'materia_id');
    }

    public function asignacionesAcademicas(): HasMany
    {
        return $this->hasMany(AsignacionAcademica::class, 'materia_id');
    }
}
