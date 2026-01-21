<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Proveedores extends Model
{
    protected $fillable = ['clave','nombre','rfc',
    'direccion','telefono','correo','contacto','team_id','dias_credito','cuenta_contable','tipo_tercero','tipo_operacion','pais'];
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
