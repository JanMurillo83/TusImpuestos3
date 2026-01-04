<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Compras extends Model
{
    protected $fillable = ['folio','fecha','prov','nombre','esquema','subtotal',
    'iva','retiva','retisr','ieps','total','moneda','tcambio','observa','estado','orden','orden_id','requisicion_id','team_id','recibe','proyecto','cfdi_id'];

    public function partidas(): HasMany
    {
        return $this->hasMany(related: ComprasPartidas::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function orden(): BelongsTo
    {
        return $this->belongsTo(Ordenes::class, 'orden_id');
    }

    public function requisicion(): BelongsTo
    {
        return $this->belongsTo(Requisiciones::class, 'requisicion_id');
    }
}
