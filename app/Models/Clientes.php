<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Clientes extends Model
{
    protected $fillable = ['clave','nombre','rfc','regimen','codigo',
    'direccion','telefono','correo','descuento','lista','contacto','team_id','dias_credito','cuenta_contable'];
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
