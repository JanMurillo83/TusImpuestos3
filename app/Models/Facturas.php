<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Services\ImpuestosCalculator;

class Facturas extends Model
{
    protected static bool $allowFolioUpdate = false;

    protected $fillable = ['serie','folio','docto','fecha','clie','nombre','rfc_mostr','nombre_mostr','esquema','subtotal',
    'iva','retiva','retisr','ieps','total','observa','estado','metodo',
    'forma','uso','uuid','remision_id','pedido_id','cotizacion_id','condiciones','vendedor','anterior','timbrado','xml','fecha_tim',
    'moneda','tcambio','fecha_cancela','motivo','sustituye','xml_cancela','pendiente_pago','team_id','error_timbrado','docto_rela','tipo_rela'];

    protected static function booted(): void
    {
        static::updating(function (self $factura): void {
            if (self::$allowFolioUpdate) {
                return;
            }

            if ($factura->isDirty('folio') || $factura->isDirty('serie')) {
                throw new \RuntimeException('No se permite actualizar serie/folio en facturas existentes.');
            }
        });
    }

    public static function sinBloqueoFolio(callable $callback): mixed
    {
        $prev = self::$allowFolioUpdate;
        self::$allowFolioUpdate = true;

        try {
            return $callback();
        } finally {
            self::$allowFolioUpdate = $prev;
        }
    }
    public function partidas(): HasMany
    {
        return $this->hasMany(related: FacturasPartidas::class);

    }
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function recalculatePartidasFromItemSchema(): void
    {
        $this->partidas()->get()->each(function (FacturasPartidas $partida): void {
            $cant = (float) $partida->cant;
            $precio = (float) $partida->precio;
            $subtotal = $cant * $precio;
            $taxes = ImpuestosCalculator::fromEsquema($this->esquema, $subtotal);

            $partida->forceFill([
                'subtotal' => $subtotal,
                'iva' => $taxes['iva'],
                'retiva' => $taxes['retiva'],
                'retisr' => $taxes['retisr'],
                'ieps' => $taxes['ieps'],
                'total' => $taxes['total'],
                'por_imp1' => $taxes['por_imp1'],
                'por_imp2' => $taxes['por_imp2'],
                'por_imp3' => $taxes['por_imp3'],
                'por_imp4' => $taxes['por_imp4'],
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
