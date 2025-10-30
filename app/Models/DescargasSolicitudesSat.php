<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DescargasSolicitudesSat extends Model
{
    protected $fillable = ['id_sat','estatus','estado','team_id','fecha_inicial','fecha_final','fecha'];
}
