<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Compras extends Model
{
    protected $fillable = ['folio','fecha','prov','nombre','esquema','subtotal',
    'iva','retiva','retisr','ieps','total','moneda','tcambio','observa','estado','orden','team_id'];
    public function partidas(): HasMany
    {
        return $this->hasMany(related: ComprasPartidas::class);

    }
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
