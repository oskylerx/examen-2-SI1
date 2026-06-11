<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Horario extends Model
{
    protected $table = 'horario';

    protected $fillable = [
        'dia',
        'turno',
        'hora_inicio',
        'hora_final',
        'activo',
    ];

    public function asignacionesAcademicas(): HasMany
    {
        return $this->hasMany(AsignacionAcademica::class, 'horario_id');
    }
}
