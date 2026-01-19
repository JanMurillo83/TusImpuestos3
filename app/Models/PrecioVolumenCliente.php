<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrecioVolumenCliente extends Model
{
    protected $table = 'precios_volumen_clientes';

    protected $fillable = [
        'cliente_id',
        'producto_id',
        'cantidad_desde',
        'cantidad_hasta',
        'precio_unitario',
        'activo',
        'prioridad',
        'vigencia_desde',
        'vigencia_hasta',
        'team_id'
    ];

    protected $casts = [
        'activo' => 'boolean',
        'cantidad_desde' => 'decimal:2',
        'cantidad_hasta' => 'decimal:2',
        'precio_unitario' => 'decimal:6',
        'vigencia_desde' => 'date',
        'vigencia_hasta' => 'date',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Clientes::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Inventario::class, 'producto_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    // Verificar si está vigente
    public function estaVigente(): bool
    {
        $hoy = now()->toDateString();

        if ($this->vigencia_desde && $hoy < $this->vigencia_desde) {
            return false;
        }

        if ($this->vigencia_hasta && $hoy > $this->vigencia_hasta) {
            return false;
        }

        return true;
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

    // Scope para activos
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    // Scope para vigentes
    public function scopeVigentes($query)
    {
        $hoy = now()->toDateString();

        return $query->where(function($q) use ($hoy) {
            $q->whereNull('vigencia_desde')
              ->orWhere('vigencia_desde', '<=', $hoy);
        })->where(function($q) use ($hoy) {
            $q->whereNull('vigencia_hasta')
              ->orWhere('vigencia_hasta', '>=', $hoy);
        });
    }
}
