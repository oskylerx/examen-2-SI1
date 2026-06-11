<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Aula extends Model
{
    protected $table = 'aula';

    protected $fillable = [
        'nombre',
        'capacidad',
        'ubicacion',
        'activo',
    ];

    public function asignacionesAcademicas(): HasMany
    {
        return $this->hasMany(AsignacionAcademica::class, 'aula_id');
    }
}
