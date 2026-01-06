<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cotizaciones extends Model
{
    protected $fillable = ['serie','folio','docto','fecha','clie','nombre','esquema','subtotal',
    'iva','retiva','retisr','ieps','total','observa','estado','metodo',
    'forma','uso','moneda','tcambio','uuid','condiciones','vendedor','siguiente','team_id',
    'entrega_lugar', 'entrega_direccion', 'entrega_horario', 'entrega_contacto', 'entrega_telefono',
    'condiciones_pago', 'condiciones_entrega', 'oc_referencia_interna', 'nombre_elaboro', 'nombre_autorizo'];
    public function partidas(): HasMany
    {
        return $this->hasMany(related: CotizacionesPartidas::class);

    }
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
