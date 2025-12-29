<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MainReportes extends Model
{
    protected $fillable = ['reporte','ruta','ruta_excel','tipo','pdf','xls'];
}
