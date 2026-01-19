<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inventario extends Model
{
    protected $fillable = ['clave','descripcion','linea','marca','modelo',
    'u_costo','p_costo','precio1','precio2','precio3','precio4','precio5',
    'exist','esquema','servicio','unidad','cvesat','team_id'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function preciosVolumen(): HasMany
    {
        return $this->hasMany(PrecioVolumen::class, 'producto_id');
    }

    public function preciosVolumenClientes(): HasMany
    {
        return $this->hasMany(PrecioVolumenCliente::class, 'producto_id');
    }
}
