<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Clientes extends Model
{
    protected $fillable = ['clave','nombre','rfc','regimen','codigo',
    'direccion','telefono','correo','correo2','descuento','lista','contacto','team_id','dias_credito','cuenta_contable',
    'calle','no_exterior','no_interior','colonia','municipio','estado'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function direccionesEntrega(): HasMany
    {
        return $this->hasMany(DireccionesEntrega::class, 'cliente_id');
    }

    public function equivalenciasInventario(): HasMany
    {
        return $this->hasMany(EquivalenciaInventarioCliente::class, 'cliente_id');
    }

    public function preciosVolumenClientes(): HasMany
    {
        return $this->hasMany(PrecioVolumenCliente::class, 'cliente_id');
    }
}
