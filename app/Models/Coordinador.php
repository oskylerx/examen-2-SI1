<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coordinador extends Model
{
    protected $table = 'coordinador';

    protected $fillable = [
        'user_id',
        'especialidad',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function grupos(): HasMany
    {
        return $this->hasMany(Grupo::class, 'coordinador_id');
    }

    public function reportes(): HasMany
    {
        return $this->hasMany(Reporte::class, 'coordinador_id');
    }

    public function docentes(): HasMany
    {
        return $this->hasMany(Docente::class, 'coordinador_id');
    }
}
