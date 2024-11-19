<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Saldosbanco extends Model
{
    use HasFactory;
    protected $fillable = ['cuenta','inicial','ingresos','egresos','actual','ejercicio','periodo'];
}
