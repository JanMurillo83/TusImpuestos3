<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class FacturasPartidas extends Model
{
    protected $fillable = ['facturas_id','item','descripcion','cant','precio',
    'subtotal','iva','retiva','retisr','ieps','total','unidad','cvesat','costo',
    'clie','observa','anterior','siguiente','por_imp1','por_imp2','por_imp3',
    'por_imp4','team_id'];
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function inven():BelongsToMany
    {
        return $this->belongsToMany(Inventario::class,'inventario.id','partidas.item');
    }
}
