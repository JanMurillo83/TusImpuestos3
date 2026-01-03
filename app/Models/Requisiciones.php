<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Requisiciones extends Model
{
    protected $fillable = ['folio','fecha','prov','nombre','esquema','subtotal',
    'iva','retiva','retisr','ieps','total','moneda','tcambio','observa','estado','compra','team_id','solicita','proyecto'];

    public function partidas(): HasMany
    {
        return $this->hasMany(related: RequisicionesPartidas::class);

    }
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
