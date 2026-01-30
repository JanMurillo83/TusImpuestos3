<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cotizaciones extends Model
{
    protected $fillable = ['serie','folio','docto','fecha','clie','nombre','esquema','subtotal',
    'iva','retiva','retisr','ieps','total','observa','estado','metodo',
    'forma','uso','moneda','tcambio','uuid','condiciones','vendedor','siguiente','team_id',
    'entrega_lugar', 'entrega_direccion', 'entrega_horario', 'entrega_contacto', 'entrega_telefono',
    'condiciones_pago', 'condiciones_entrega', 'oc_referencia_interna', 'nombre_elaboro', 'nombre_autorizo',
    'direccion_entrega_id'];
    public function partidas(): HasMany
    {
        return $this->hasMany(related: CotizacionesPartidas::class);

    }
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function direccionEntrega(): BelongsTo
    {
        return $this->belongsTo(DireccionesEntrega::class, 'direccion_entrega_id');
    }

    public function syncClienteNombre(): void
    {
        if (!$this->clie) {
            return;
        }

        $cliente = Clientes::find($this->clie);
        if (!$cliente) {
            return;
        }

        if ($this->nombre !== $cliente->nombre) {
            $this->forceFill(['nombre' => $cliente->nombre])->save();
        }
    }

    public function fixPartidasSubtotalFromCantidadPrecio(): void
    {
        if (!$this->esquema) {
            return;
        }

        $esq = Esquemasimp::find($this->esquema);
        if (!$esq) {
            return;
        }

        $this->partidas()->get()->each(function (CotizacionesPartidas $partida) use ($esq): void {
            $cant = (float) $partida->cant;
            $precio = (float) $partida->precio;
            $subtotalActual = (float) $partida->subtotal;

            if ($cant <= 0 || $precio <= 0 || $subtotalActual > 0) {
                return;
            }

            $subtotal = $cant * $precio;
            $iva = $subtotal * ((float) $esq->iva * 0.01);
            $retiva = $subtotal * ((float) $esq->retiva * 0.01);
            $retisr = $subtotal * ((float) $esq->retisr * 0.01);
            $ieps = $subtotal * ((float) $esq->ieps * 0.01);
            $total = $subtotal + $iva - $retiva - $retisr + $ieps;

            $partida->forceFill([
                'subtotal' => $subtotal,
                'iva' => $iva,
                'retiva' => $retiva,
                'retisr' => $retisr,
                'ieps' => $ieps,
                'total' => $total,
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
