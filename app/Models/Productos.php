<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Productos extends Model
{
    use HasFactory;
    protected $fillable = ['clave','descripcion','unidad','clavesat',
    'existencia','precio','costo_u','costo_p','codigo','team_id'];
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
