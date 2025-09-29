<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FacturaModelo extends Model
{
    protected $table = 'factura_modelos';

    protected $fillable = [
        'team_id','nombre_modelo','clie','cliente_nombre','esquema','metodo','forma','uso','moneda','tcambio',
        'condiciones','observa','vendedor','periodicidad','cada_dias','proxima_emision','ultima_emision','activa',
        'subtotal','iva','retiva','retisr','ieps','total'
    ];

    protected $casts = [
        'proxima_emision' => 'date',
        'ultima_emision' => 'date',
        'activa' => 'boolean',
    ];

    public function partidas(): HasMany
    {
        return $this->hasMany(FacturaModeloPartida::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
