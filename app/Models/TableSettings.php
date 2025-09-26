<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TableSettings extends Model
{
    protected $table = 'table_settings';
    protected $fillable = ['user_id','resource','styles','settings','team_id'];

    protected $casts = [
        'styles' => 'array',
        'settings' => 'array',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
