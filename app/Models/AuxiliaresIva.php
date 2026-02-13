<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuxiliaresIva extends Model
{
    use HasFactory;

    protected $table = 'auxiliares_iva';

    protected $fillable = [
        'auxiliares_id',
        'team_id',
        'base_gravable',
        'tasa_iva',
        'importe_iva',
        'retencion_iva',
        'retencion_isr',
        'ieps',
        'tipo_operacion',
        'tipo_comprobante',
        'metodo_pago',
        'uuid',
        'folio_fiscal',
    ];

    protected $casts = [
        'base_gravable' => 'decimal:2',
        'tasa_iva' => 'decimal:2',
        'importe_iva' => 'decimal:2',
        'retencion_iva' => 'decimal:2',
        'retencion_isr' => 'decimal:2',
        'ieps' => 'decimal:2',
    ];

    /**
     * Relación con Auxiliares
     */
    public function auxiliar(): BelongsTo
    {
        return $this->belongsTo(Auxiliares::class, 'auxiliares_id');
    }

    /**
     * Relación con Team
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
