<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotadeCredito extends Model
{
    protected $fillable = ['serie','folio','docto','fecha','clie','nombre','esquema','subtotal',
        'iva','retiva','retisr','ieps','total','observa','estado','metodo',
        'forma','uso','uuid','condiciones','vendedor','anterior','timbrado','xml','fecha_tim',
        'moneda','tcambio','fecha_cancela','motivo','sustituye','xml_cancela','pendiente_pago','team_id','error_timbrado','docto_rela','tipo_rela'];
    public function partidas(): HasMany
    {
        return $this->hasMany(related: PartidasNotadeCredito::class);

    }
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
