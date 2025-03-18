<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PagoNominas extends Model
{
    protected $fillable = ['fecha','nonom','tipo','fecha_pa','sueldo','ret_isr','ret_imss','subsidio','otras_per','otras_ded','importe','movban','estado','team_id'];
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
    public function Detalles(): HasMany
    {
        return $this->hasMany(related: PagoNominasDetalles::class);
    }
}
