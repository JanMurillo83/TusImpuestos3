<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pedidos extends Model
{
    protected $fillable = ['serie','folio','docto','fecha','clie','nombre','esquema','subtotal',
        'iva','retiva','retisr','ieps','total','observa','estado','metodo',
        'forma','uso','moneda','tcambio','uuid','condiciones','vendedor','siguiente','team_id'];
    public function partidas(): HasMany
    {
        return $this->hasMany(related: PedidosPartidas::class);
    }
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
