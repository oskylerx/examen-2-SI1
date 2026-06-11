<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentoPostulante extends Model
{
    protected $table = 'documento_postulante';

    protected $fillable = [
        'postulante_id',
        'nombre',
        'tipo',
        'archivo',
        'otro',
        'estado',
        'fecha_subida',
    ];

    protected function casts(): array
    {
        return [
            'fecha_subida' => 'date',
        ];
    }

    public function postulante(): BelongsTo
    {
        return $this->belongsTo(Postulante::class, 'postulante_id');
    }
}