<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReporteResumenEjecutivo extends Model
{
    protected $table = 'reportes_resumen_ejecutivo';

    protected $fillable = [
        'tenant_id',
        'periodo',
        'datos',
        'reporte',
    ];

    protected $casts = [
        'datos' => 'array',
    ];
}
