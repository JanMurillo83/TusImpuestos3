<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeriesFacturas extends Model
{
    protected $fillable = ['serie', 'tipo', 'folio','team_id'];
}
