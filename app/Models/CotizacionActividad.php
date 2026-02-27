<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CotizacionActividad extends Model
{
    protected $table = 'cotizacion_actividades';

    protected $fillable = [
        'cotizacion_id',
        'user_id',
        'tipo',
        'fecha',
        'resultado',
        'proxima_accion',
        'proxima_fecha',
    ];

    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizaciones::class, 'cotizacion_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
