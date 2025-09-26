<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComprasPartidas extends Model
{
    protected $fillable = ['compras_id','item','descripcion','cant','costo',
    'subtotal','iva','retiva','retisr','ieps','total','moneda','tcambio','unidad','cvesat',
    'prov','observa','idorden','team_id'];
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
