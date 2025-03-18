<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PagoNominasDetalles extends Model
{
    protected $fillable = ['pago_nominas_id','empleado','recibo','sueldo','ret_isr','ret_imss','subsidio','otras_per','otras_ded','importe'];
}
