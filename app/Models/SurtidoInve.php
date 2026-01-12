<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurtidoInve extends Model
{
    protected $fillable = ['factura_id','factura_partida_id','item_id','descr','cant','precio_u','costo_u','precio_total','costo_total','estado','team_id'];
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
