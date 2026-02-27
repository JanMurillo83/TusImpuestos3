<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComercialCanal extends Model
{
    protected $table = 'comercial_canales';

    protected $fillable = ['team_id', 'nombre', 'activo', 'sort'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
