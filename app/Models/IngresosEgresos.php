<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IngresosEgresos extends Model
{
    use HasFactory;
    protected $fillable = ['xml_id','poliza','subtotalusd','ivausd',
    'totalusd','subtotalmxn','ivamxn','totalmxn','tcambio','uuid','referencia','pendientemxn','pendienteusd','pagadousd','pagadomxn','periodo','ejercicio','team_id'];
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
