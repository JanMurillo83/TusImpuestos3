<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrdenesInsumosPartidas extends Model
{
    protected $fillable = [
        'ordenes_insumos_id',
        'item',
        'descripcion',
        'cant',
        'pendientes',
        'costo',
        'subtotal',
        'iva',
        'retiva',
        'retisr',
        'ieps',
        'total',
        'moneda',
        'tcambio',
        'unidad',
        'cvesat',
        'prov',
        'observa',
        'requisicion_partida_id',
        'team_id',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
