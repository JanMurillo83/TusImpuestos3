<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mailconfig extends Model
{
    protected $fillable = ['host','port','username','password','from_name','from_address','team_id'];
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
