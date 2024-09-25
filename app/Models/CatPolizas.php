<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CatPolizas extends Model
{
    use HasFactory;
    protected $fillable = ['tipo',
    'folio',
    'fecha',
    'concepto',
    'cargos',
    'abonos',
    'periodo',
    'ejercicio',
    'referencia',
    'uuid',
    'tiposat',
    'team_id'];
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
    public function partidas(): BelongsToMany
    {
        return $this->BelongsToMany(Auxiliares::class);
    }
}
