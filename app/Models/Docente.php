<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Docente extends Model
{
    protected $table = 'docente';

    protected $fillable = [
        'user_id',
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
}