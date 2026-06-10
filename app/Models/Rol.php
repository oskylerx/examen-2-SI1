<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nombre'])]
class Rol extends Model
{
    protected $table = 'roles';

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'rol_id');
    }
}