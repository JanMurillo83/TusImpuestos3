<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Seriesfac extends Model
{
    use HasFactory;
    protected $fillable =['serie', 'tipo', 'folio', 'team_id'];
}
