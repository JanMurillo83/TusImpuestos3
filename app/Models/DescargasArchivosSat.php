<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DescargasArchivosSat extends Model
{
    protected $fillable = ['id_sat','team_id','fecha','archivo','estado'];
}
