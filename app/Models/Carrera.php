<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Carrera extends Model
{
    protected $table = 'carrera';

    protected $fillable = [
        'codigo_carrera',
        'nombre',
        'descripcion',
        'cupos',
        'nota_min_ingreso',
        'estado',
    ];

    public function postulantesPrimeraOpcion(): HasMany
    {
        return $this->hasMany(Postulante::class, 'primera_opcion_carrera_id');
    }

    public function postulantesSegundaOpcion(): HasMany
    {
        return $this->hasMany(Postulante::class, 'segunda_opcion_carrera_id');
    }

    public function grupos(): HasMany
    {
        return $this->hasMany(Grupo::class, 'carrera_id');
    }

    public function asignacionesCupo(): HasMany
    {
        return $this->hasMany(AsignacionCupo::class, 'carrera_id');
    }
}
