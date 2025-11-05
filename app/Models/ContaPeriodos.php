<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContaPeriodos extends Model
{
    protected $fillable = ['periodo','ejercicio','estado','team_id'];
}
