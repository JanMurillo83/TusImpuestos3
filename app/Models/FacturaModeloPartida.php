<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacturaModeloPartida extends Model
{
    protected $table = 'factura_modelo_partidas';

    protected $fillable = [
        'factura_modelo_id','item','descripcion','cant','precio','subtotal','iva','retiva','retisr','ieps','total',
        'unidad','cvesat','costo','team_id'
    ];

    public function plantilla(): BelongsTo
    {
        return $this->belongsTo(FacturaModelo::class, 'factura_modelo_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
