<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConceptosCP extends Model
{
    protected $fillable = ['clave','descripcion','tipo','signo','pagosat'];
}
