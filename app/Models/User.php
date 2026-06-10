<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'rol_id',
        'username',
        'ci',
        'name',
        'apellido',
        'telefono',
        'email',
        'password',
        'estado',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'rol_id');
    }

    public function administrador(): HasOne
    {
        return $this->hasOne(Administrador::class, 'user_id');
    }

    public function coordinador(): HasOne
    {
        return $this->hasOne(Coordinador::class, 'user_id');
    }

    public function docente(): HasOne
    {
        return $this->hasOne(Docente::class, 'user_id');
    }

    public function postulante(): HasOne
    {
        return $this->hasOne(Postulante::class, 'user_id');
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}