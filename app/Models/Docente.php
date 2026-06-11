<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Docente extends Model
{
    protected $table = 'docente';

    protected $fillable = [
        'user_id',
        'coordinador_id',
        'profesion',
        'especialidad',
        'maestria',
        'diplomado',
        'estado_validacion',
        'observacion',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function grupos(): HasMany
    {
        return $this->hasMany(Grupo::class, 'docente_id');
    }

    public function asignacionesAcademicas(): HasMany
    {
        return $this->hasMany(AsignacionAcademica::class, 'docente_id');
    }

    public function coordinador(): BelongsTo
    {
        return $this->belongsTo(Coordinador::class, 'coordinador_id');
    }
}
