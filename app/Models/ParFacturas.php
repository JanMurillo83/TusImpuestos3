<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParFacturas extends Model
{
    use HasFactory;

    protected $fillable = ['facturas_id', 'serie', 'folio', 'clave_doc',
    'cve_clie', 'cve_vend', 'fecha_doc', 'cant', 'id_prod', 'cve_prod',
    'descripcion', 'unidad', 'precio', 'subtotal', 'impuesto1', 'impuesto2',
    'impuesto3', 'impuesto4', 'descuento', 'por_im1', 'por_im2', 'por_im3',
    'por_im4', 'por_des', 'total', 'cvesat', 'unisat','team_id'];
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
