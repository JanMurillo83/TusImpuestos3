<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaldosAnuales extends Model
{
    protected $fillable = [
        'codigo','acumula','descripcion','tipo','naturaleza','inicial','c1','a1','f1','c2','a2',
        'f2','c3','a3','f3','c4','a4','f4','c5','a5','f5','c6','a6','f6','c7','a7','f7','c8','a8',
        'f8','c9','a9','f9','c10','a10','f10','c11','a11','f11','c12','a12','f12','team_id'
    ];
}
