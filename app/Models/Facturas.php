<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Facturas extends Model
{
    protected $fillable = ['serie','folio','docto','fecha','clie','nombre','esquema','subtotal',
    'iva','retiva','retisr','ieps','total','observa','estado','metodo',
    'forma','uso','uuid','condiciones','vendedor','anterior','timbrado','xml','fecha_tim','team_id'];
    public function partidas(): HasMany
    {
        return $this->hasMany(related: FacturasPartidas::class);

    }
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
