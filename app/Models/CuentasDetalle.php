<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CuentasDetalle extends Model
{
    protected $table = 'cuentas_detalle_view';
    protected $fillable = ['id','codigo','nombre','team_id'];
}
