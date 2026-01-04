<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Facturas extends Model
{
    protected $fillable = ['serie','folio','docto','fecha','clie','nombre','esquema','subtotal',
    'iva','retiva','retisr','ieps','total','observa','estado','metodo',
    'forma','uso','uuid','remision_id','pedido_id','cotizacion_id','condiciones','vendedor','anterior','timbrado','xml','fecha_tim',
    'moneda','tcambio','fecha_cancela','motivo','sustituye','xml_cancela','pendiente_pago','team_id','error_timbrado','docto_rela','tipo_rela'];
    public function partidas(): HasMany
    {
        return $this->hasMany(related: FacturasPartidas::class);

    }
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
