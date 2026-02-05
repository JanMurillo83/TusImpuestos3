<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Services\ImpuestosCalculator;

class OrdenesInsumos extends Model
{
    protected $fillable = [
        'folio',
        'fecha',
        'prov',
        'nombre',
        'esquema',
        'subtotal',
        'iva',
        'retiva',
        'retisr',
        'ieps',
        'total',
        'moneda',
        'tcambio',
        'observa',
        'estado',
        'requisicion_id',
        'team_id',
        'solicita',
        'proyecto',
        'entrega_lugar',
        'entrega_direccion',
        'entrega_horario',
        'entrega_contacto',
        'entrega_telefono',
        'condiciones_pago',
        'condiciones_entrega',
        'oc_referencia_interna',
        'nombre_elaboro',
        'nombre_autorizo',
    ];

    public function partidas(): HasMany
    {
        return $this->hasMany(related: OrdenesInsumosPartidas::class, foreignKey: 'ordenes_insumos_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function recalculatePartidasFromItemSchema(): void
    {
        $this->partidas()->get()->each(function (OrdenesInsumosPartidas $partida): void {
            $cant = (float) $partida->cant;
            $costo = (float) $partida->costo;
            $subtotal = $cant * $costo;
            $taxes = ImpuestosCalculator::fromEsquema($this->esquema, $subtotal);

            $partida->forceFill([
                'subtotal' => $subtotal,
                'iva' => $taxes['iva'],
                'retiva' => $taxes['retiva'],
                'retisr' => $taxes['retisr'],
                'ieps' => $taxes['ieps'],
                'total' => $taxes['total'],
            ])->save();
        });
    }

    public function recalculateTotalsFromPartidas(): void
    {
        $totals = $this->partidas()
            ->selectRaw('COALESCE(SUM(subtotal), 0) as subtotal')
            ->selectRaw('COALESCE(SUM(iva), 0) as iva')
            ->selectRaw('COALESCE(SUM(retiva), 0) as retiva')
            ->selectRaw('COALESCE(SUM(retisr), 0) as retisr')
            ->selectRaw('COALESCE(SUM(ieps), 0) as ieps')
            ->selectRaw('COALESCE(SUM(total), 0) as total')
            ->first();

        $this->forceFill([
            'subtotal' => $totals->subtotal ?? 0,
            'iva' => $totals->iva ?? 0,
            'retiva' => $totals->retiva ?? 0,
            'retisr' => $totals->retisr ?? 0,
            'ieps' => $totals->ieps ?? 0,
            'total' => $totals->total ?? 0,
        ])->save();
    }
}
