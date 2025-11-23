<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Admincuentaspagar extends Model
{
    protected $fillable = ['clave','referencia','uuid','fecha','vencimiento',
        'moneda','tcambio','importe','importeusd','saldo','saldousd',
        'periodo','ejercicio','periodo_ven','ejercicio_ven','poliza','team_id'];
}
