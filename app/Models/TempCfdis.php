<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TempCfdis extends Model
{
    protected $fillable = ['UUID','RfcEmisor','NombreEmisor','RfcReceptor','NombreReceptor',
    'RfcPac','FechaEmision','FechaCertificacionSat','Monto','EfectoComprobante','Estatus',
    'FechaCancelacion','Tipo','team_id'];
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
