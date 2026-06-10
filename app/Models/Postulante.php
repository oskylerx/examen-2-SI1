<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Postulante extends Model
{
    protected $table = 'postulante';

    protected $fillable = [
        'user_id',
        'fecha_nacimiento',
        'genero',
        'direccion',
        'colegio',
        'ciudad',
        'titulo_bachiller',
        'otros_documentos',
        'fecha_registro',
        'estado_inscripcion',
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
}