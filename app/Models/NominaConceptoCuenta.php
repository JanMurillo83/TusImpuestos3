<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NominaConceptoCuenta extends Model
{
    protected $table = 'nomina_concepto_cuentas';

    protected $fillable = [
        'team_id',
        'tipo',
        'codigo_sat',
        'clave',
        'descripcion',
        'cat_cuentas_id',
        'naturaleza',
        'activo',
    ];

    public function catCuenta()
    {
        return $this->belongsTo(CatCuentas::class, 'cat_cuentas_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
