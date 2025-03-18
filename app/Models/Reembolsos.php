<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reembolsos extends Model
{
    protected $fillable = ['fecha','periodo','ejercicio','movbanco','importe','importe_comp','estado',
    'idtercero','nombre','formapago','descrfpago','descripcion','notas','team_id'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
    public function Detalles(): HasMany
    {
        return $this->hasMany(related: ReembolsosDetalles::class);
    }
}
