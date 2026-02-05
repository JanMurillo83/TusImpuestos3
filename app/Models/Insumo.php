<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Insumo extends Model
{
    protected $fillable = [
        'clave',
        'descripcion',
        'linea',
        'marca',
        'modelo',
        'u_costo',
        'p_costo',
        'precio1',
        'precio2',
        'precio3',
        'precio4',
        'precio5',
        'exist',
        'esquema',
        'servicio',
        'unidad',
        'cvesat',
        'team_id',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
