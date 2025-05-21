<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaldosReportes extends Model
{
    use HasFactory;
    protected $fillable = ['codigo','cuenta','acumula','naturaleza','anterior','cargos','abonos','final','team_id'];
}
