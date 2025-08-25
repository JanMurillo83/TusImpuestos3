<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CuentasCobrar extends Model
{
    protected $fillable = ['cliente','concepto','descripcion','documento','fecha','vencimiento','importe','saldo'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
