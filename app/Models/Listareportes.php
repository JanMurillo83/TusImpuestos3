<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Listareportes extends Model
{
    protected $fillable = ['nombre','descripcion','ruta','tipo','team_id'];
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
