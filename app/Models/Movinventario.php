<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Movinventario extends Model
{
    protected $fillable = ['producto','tipo','fecha','cant','costo','precio',
    'concepto','tipoter','tercero','team_id'];
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
