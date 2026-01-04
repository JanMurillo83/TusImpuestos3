<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Remisiones extends Model
{
    protected $fillable = ['serie','folio','docto','fecha','clie','nombre','esquema','subtotal',
        'iva','retiva','retisr','ieps','total','observa','estado','metodo',
        'forma','uso','moneda','tcambio','uuid','pedido_id','cotizacion_id','condiciones','vendedor','siguiente','team_id'];

    public function partidas(): HasMany
    {
        return $this->hasMany(related: RemisionesPartidas::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
