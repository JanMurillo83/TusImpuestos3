<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Saldoscuentas extends Model
{
    use HasFactory;
    protected $fillable = ['codigo','nombre', 'n1', 'n2','n3', 'n4', 'n5', 'n6', 'si','c1',
    'c2', 'c3', 'c4', 'c5', 'c6', 'c7', 'c8', 'c9', 'c10', 'c11', 'c12', 'a1', 'a2', 'a3', 'a4', 'a5',
    'a6', 'a7', 'a8', 'a9', 'a10', 'a11', 'a12', 's1','s2', 's3', 's4', 's5', 's6', 's7', 's8', 's9',
    's10', 's11', 's12',
    'team_id'];
}
