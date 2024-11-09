<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BuscaSat extends Model
{
    protected $table = 'busca_sat';

    protected $fillable = [
		'clave',
		'nombre'
	];
}
