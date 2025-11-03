<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CuentasPagar extends Model
{
    protected $fillable = ['proveedor','concepto','descripcion','documento','fecha','vencimiento','importe','saldo','team_id','refer'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
