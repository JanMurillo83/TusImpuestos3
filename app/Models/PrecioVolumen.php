<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrecioVolumen extends Model
{
    protected $table = 'precios_volumen';

    protected $fillable = [
        'producto_id',
        'lista_precio',
        'cantidad_desde',
        'cantidad_hasta',
        'precio_unitario',
        'activo',
        'team_id'
    ];

    protected $casts = [
        'activo' => 'boolean',
        'cantidad_desde' => 'decimal:2',
        'cantidad_hasta' => 'decimal:2',
        'precio_unitario' => 'decimal:6',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Inventario::class, 'producto_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    // Scope para buscar precio según cantidad
    public function scopeParaCantidad($query, $cantidad)
    {
        return $query->where('cantidad_desde', '<=', $cantidad)
                     ->where(function($q) use ($cantidad) {
                         $q->whereNull('cantidad_hasta')
                           ->orWhere('cantidad_hasta', '>=', $cantidad);
                     });
    }

    // Scope para una lista específica
    public function scopeParaLista($query, $lista)
    {
        return $query->where('lista_precio', $lista);
    }

    // Scope para activos
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
