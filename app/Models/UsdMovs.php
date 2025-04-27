<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsdMovs extends Model
{
    use HasFactory;
    protected $fillable = ['xml_id','poliza','subtotalusd','ivausd',
    'totalusd','subtotalmxn','ivamxn','totalmxn','tcambio','uuid','referencia'];
}
