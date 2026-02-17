<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ValidaDescargas extends Model
{
    protected $fillable = ['fecha','inicio','fin','recibidos','emitidos','estado','team_id'];

    /**
     * Relación con Team
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
