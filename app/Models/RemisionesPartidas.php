<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RemisionesPartidas extends Model
{
    protected $fillable = ['remisiones_id','item','descripcion','cant',
        'pendientes','precio','subtotal','iva','retiva','retisr','ieps',
        'total','unidad','cvesat','costo','clie','observa','anterior','siguiente',
        'team_id'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function remision(): BelongsTo
    {
        return $this->belongsTo(Remisiones::class, 'remisiones_id');
    }
}
