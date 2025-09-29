<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Proyectos extends Model
{
    protected $fillable = ['clave','descripcion','compras','ventas','estado','team_id'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
