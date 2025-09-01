<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParPagos extends Model
{
    protected $fillable =['pagos_id','uuidrel','cvesat','unisat','cant','unitario',
        'importe','moneda','equivalencia','parcialidad','saldoant','imppagado',
        'insoluto','objeto','tasaiva','baseiva','montoiva','team_id'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
