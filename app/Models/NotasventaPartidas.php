<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotasventaPartidas extends Model
{
    protected $fillable = ['notasventas_id','item','descripcion','cant','precio',
    'subtotal','iva','retiva','retisr','ieps','total','unidad','cvesat','costo',
    'clie','observa','anterior','siguiente','team_id'];
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
