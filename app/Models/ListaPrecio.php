<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListaPrecio extends Model
{
    protected $table = 'listas_precios';

    protected $fillable = ['lista', 'nombre', 'team_id'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
