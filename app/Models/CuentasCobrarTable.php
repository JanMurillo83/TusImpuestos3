<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CuentasCobrarTable extends Model
{
    protected $fillable = ['cliente','documento','uuid','concepto','fecha','vencimiento','importe','saldo','tipo','periodo','ejercicio','team_id'];
}
