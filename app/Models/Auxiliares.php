<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Auxiliares extends Model
{
    use HasFactory;

    protected $fillable = ['cat_polizas_id',
        'codigo',
        'cuenta',
        'concepto',
        'cargo',
        'abono',
        'factura',
        'nopartida',
        'uuid',
        'team_id',
        'igeg_id',
        'a_periodo',
        'a_ejercicio'
    ];

    /**
     * Relación con datos de IVA
     */
    public function iva(): HasOne
    {
        return $this->hasOne(AuxiliaresIva::class, 'auxiliares_id');
    }

    /**
     * Relación con datos de DIOT
     */
    public function diot(): HasOne
    {
        return $this->hasOne(AuxiliaresDiot::class, 'auxiliares_id');
    }

    /**
     * Relación con la póliza
     */
    public function poliza(): BelongsTo
    {
        return $this->belongsTo(CatPolizas::class, 'cat_polizas_id');
    }
}
