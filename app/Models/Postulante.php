<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Postulante extends Model
{
    protected $table = 'postulante';

    protected $fillable = [
        'user_id',
        'grupo_id',
        'primera_opcion_carrera_id',
        'segunda_opcion_carrera_id',
        'fecha_nacimiento',
        'genero',
        'direccion',
        'colegio',
        'ciudad',
        'fecha_registro',
        'estado_inscripcion',
        'observacion',
    ];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'fecha_registro' => 'date',
            'titulo_bachiller' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'postulante_id');
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(DocumentoPostulante::class, 'postulante_id');
    }

    public function primeraOpcionCarrera(): BelongsTo
    {
        return $this->belongsTo(Carrera::class, 'primera_opcion_carrera_id');
    }

    public function segundaOpcionCarrera(): BelongsTo
    {
        return $this->belongsTo(Carrera::class, 'segunda_opcion_carrera_id');
    }

    public function grupo()
    {
        return $this->belongsTo(Grupo::class, 'grupo_id');
    }

    public function examenes(): HasMany
    {
        return $this->hasMany(Examen::class, 'postulante_id');
    }

    public function asignacionCupo(): HasOne
    {
        return $this->hasOne(AsignacionCupo::class, 'postulante_id');
    }
}
