<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ValidaDescargas extends Model
{
    protected $fillable = ['fecha','inicio','fin','recibidos','emitidos','estado','team_id'];
}
