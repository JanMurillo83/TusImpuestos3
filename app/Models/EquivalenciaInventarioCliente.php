<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquivalenciaInventarioCliente extends Model
{
    protected $table = 'equivalencias_inventario_clientes';

    protected $fillable = [
        'cliente_id',
        'clave_articulo',
        'clave_cliente',
        'descripcion_articulo',
        'descripcion_cliente',
        'precio_cliente',
        'team_id',
    ];

    protected $casts = [
        'precio_cliente' => 'decimal:8',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Clientes::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
