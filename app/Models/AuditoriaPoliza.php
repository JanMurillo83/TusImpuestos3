<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditoriaPoliza extends Model
{
    use HasFactory;

    protected $table = 'auditoria_polizas';

    protected $fillable = [
        'poliza_id',
        'accion',
        'user_id',
        'user_name',
        'user_email',
        'datos_anteriores',
        'datos_nuevos',
        'origen',
        'fecha_hora',
    ];

    protected $casts = [
        'datos_anteriores' => 'array',
        'datos_nuevos' => 'array',
        'fecha_hora' => 'datetime',
    ];

    public function poliza(): BelongsTo
    {
        return $this->belongsTo(CatPolizas::class, 'poliza_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
