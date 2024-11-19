<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class archivos_pdf extends Model
{
    use HasFactory;
    protected $fillable = ['archivo','empresa','fecha'];
}
