<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegTraspasos extends Model
{
    protected $fillable = ['periodo','ejercicio','mov_ent','mov_sal','poliza','team_id'];
}
