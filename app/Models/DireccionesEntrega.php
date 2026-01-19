<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DireccionesEntrega extends Model
{
    protected $fillable = [
        'cliente_id',
        'nombre_sucursal',
        'calle',
        'no_exterior',
        'no_interior',
        'colonia',
        'municipio',
        'estado',
        'codigo_postal',
        'telefono',
        'contacto',
        'es_principal'
    ];

    protected $casts = [
        'es_principal' => 'boolean',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Clientes::class);
    }
}
