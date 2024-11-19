<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Movinventarios extends Model
{
    use HasFactory;
    protected $fillable = ['folio','fecha','tipo','producto','descripcion',
    'concepto','tipoter','idter','nomter','cant','costou','costot','preciou','preciot',
    'periodo','ejercicio','team_id'];
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
