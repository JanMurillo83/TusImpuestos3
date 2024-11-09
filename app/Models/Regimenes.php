<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Regimenes extends Model
{
    use HasFactory;
    protected $fillable = ['clave','descripcion','mostrar'];
}
