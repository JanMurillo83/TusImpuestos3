<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pagos extends Model
{
    protected $fillable =[ 'serie','folio','clave_doc','cve_clie','fecha_doc','fecha_can',
        'subtotal','iva','total','moneda','dat_fiscal','estado','timbrado','fecha_tim','xml','uuid',
        'usocfdi','forma','fechapago','tcambio','fecha_cancela','motivo','sustituye','xml_cancela','team_id'];

    public function Partidas(): HasMany
    {
        return $this->hasMany(related: ParPagos::class);

    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
