<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctosRelacionados extends Model
{
    protected $fillable = ['docto_type','docto_id','rel_id','rel_type','rel_cause','team_id'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
