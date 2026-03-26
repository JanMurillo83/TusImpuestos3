<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CotizacionDraft extends Model
{
    protected $table = 'cotizacion_drafts';

    protected $fillable = [
        'team_id',
        'user_id',
        'cotizacion_id',
        'draft_key',
        'payload',
        'payload_hash',
        'saved_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'saved_at' => 'datetime',
    ];
}
