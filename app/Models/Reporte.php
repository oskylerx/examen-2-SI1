<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reporte extends Model
{
    protected $table = 'reporte';

    protected $fillable = [
        'grupo_id',
        'coordinador_id',
        'fecha_reporte',
        'descripcion',
        'observaciones',
    ];

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class, 'grupo_id');
    }

    public function coordinador(): BelongsTo
    {
        return $this->belongsTo(Coordinador::class, 'coordinador_id');
    }
}