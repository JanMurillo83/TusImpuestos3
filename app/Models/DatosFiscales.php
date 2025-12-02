<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DatosFiscales extends Model
{
    protected $fillable = ['nombre','rfc','regimen','codigo','cer','key',
    'csdpass','logo','logo64','team_id','direccion','correo','telefono','coeficiente','porcentaje'];
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
