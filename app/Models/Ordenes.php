<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ordenes extends Model
{
    protected $fillable = ['folio','fecha','prov','nombre','esquema','subtotal',
    'iva','retiva','retisr','ieps','total','moneda','tcambio','observa','estado','compra','requisicion_id','team_id','solicita','proyecto',
    'entrega_lugar', 'entrega_direccion', 'entrega_horario', 'entrega_contacto', 'entrega_telefono',
    'condiciones_pago', 'condiciones_entrega', 'oc_referencia_interna', 'nombre_elaboro', 'nombre_autorizo'];

    public function partidas(): HasMany
    {
        return $this->hasMany(related: OrdenesPartidas::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function requisicion(): BelongsTo
    {
        return $this->belongsTo(Requisiciones::class, 'requisicion_id');
    }

    public function compras(): HasMany
    {
        return $this->hasMany(Compras::class, 'orden_id');
    }
}
