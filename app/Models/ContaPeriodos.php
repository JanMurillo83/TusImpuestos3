<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContaPeriodos extends Model
{
    protected $fillable = ['periodo','ejercicio','estado','team_id','es_ajuste'];

    protected $casts = [
        'es_ajuste' => 'boolean',
    ];

    public function scopePeriodoAjuste($query, $ejercicio, $team_id)
    {
        return $query->where('periodo', 13)
                    ->where('ejercicio', $ejercicio)
                    ->where('team_id', $team_id)
                    ->where('es_ajuste', true);
    }
}
