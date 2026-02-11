<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
