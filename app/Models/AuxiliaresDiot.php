<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuxiliaresDiot extends Model
{
    use HasFactory;

    protected $table = 'auxiliares_diot';

    protected $fillable = [
        'auxiliares_id',
        'team_id',
        'rfc_proveedor',
        'nombre_proveedor',
        'pais_residencia',
        'tipo_operacion',
        'tipo_tercero',
        'importe_pagado_16',
        'iva_pagado_16',
        'importe_pagado_8',
        'iva_pagado_8',
        'importe_pagado_0',
        'importe_exento',
        'iva_retenido',
        'isr_retenido',
        'numero_operacion',
        'fecha_operacion',
        'incluir_en_diot',
    ];

    protected $casts = [
        'importe_pagado_16' => 'decimal:2',
        'iva_pagado_16' => 'decimal:2',
        'importe_pagado_8' => 'decimal:2',
        'iva_pagado_8' => 'decimal:2',
        'importe_pagado_0' => 'decimal:2',
        'importe_exento' => 'decimal:2',
        'iva_retenido' => 'decimal:2',
        'isr_retenido' => 'decimal:2',
        'fecha_operacion' => 'date',
        'incluir_en_diot' => 'boolean',
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
