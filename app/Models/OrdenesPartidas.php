<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrdenesPartidas extends Model
{
    protected $fillable = ['ordenes_id','item','descripcion','cant','pendientes',
    'costo','subtotal','iva','retiva','retisr','ieps','total','moneda','tcambio','unidad',
    'cvesat','prov','observa','idcompra','team_id'];
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
