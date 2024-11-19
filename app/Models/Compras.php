<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ParCotizaciones;
use \Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Compras extends Model
{
    use HasFactory;

    protected $fillable = ['serie', 'folio', 'clave_doc', 'cve_clie', 'cve_vend', 'fecha_doc',
        'fecha_can', 'subtotal', 'impuesto1', 'impuesto2', 'impuesto3', 'impuesto4',
        'descuento', 'total', 'por_im1', 'por_im2', 'por_im3', 'por_im4', 'por_des',
        'condiciones', 'observaciones', 'dir_entrega', 'dat_fiscal', 'estado',
        'timbrado', 'fecha_tim', 'xml', 'metodo','forma', 'uuid', 'usocfdi',
        'traslados','retenciones','pdf_file','emisor','team_id'];

    public function Partidas(): HasMany
    {
        return $this->hasMany(related: ParCompras::class);

    }
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}


