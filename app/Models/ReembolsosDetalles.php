<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReembolsosDetalles extends Model
{
    protected $fillable = ['reembolsos_id','comprobante','referencia','fecha','moneda','importe','notas','gasto'];
}
