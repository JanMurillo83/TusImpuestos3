<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CuentasCobrar extends Model
{
    protected $fillable = ['cliente','concepto','descripcion','documento','fecha','vencimiento','importe','saldo','team_id','refer'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
