<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Xmlfiles extends Model
{
    protected $table = 'xmlfiles';

    protected $fillable = ['taxid','uuid','content','periodo',
    'ejercicio','tipo','solicitud','team_id'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
