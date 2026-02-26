<?php

namespace App\Services;

use App\Models\Facturas;
use App\Models\SeriesFacturas;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class FacturaFolioService
{
    public const DEFAULT_MAX_RETRIES = 3;

    /**
     * Crea una factura asignando un folio de forma segura.
     *
     * @throws QueryException
     */
    public static function crearConFolioSeguro(int $serieId, array $data, int $maxRetries = self::DEFAULT_MAX_RETRIES): Facturas
    {
        $attempt = 0;

        while (true) {
            $attempt++;

            try {
                return DB::transaction(function () use ($serieId, $data) {
                    $folioData = SeriesFacturas::obtenerSiguienteFolio($serieId);

                    $data['serie'] = $folioData['serie'];
                    $data['folio'] = $folioData['folio'];
                    $data['docto'] = $folioData['docto'] ?? ($folioData['serie'] . $folioData['folio']);

                    return Facturas::create($data);
                });
            } catch (QueryException $e) {
                if (! static::esErrorFolioDuplicado($e) || $attempt >= $maxRetries) {
                    throw $e;
                }

                static::sincronizarSerieConMax($serieId);
            }
        }
    }

    public static function esErrorFolioDuplicado(QueryException $e): bool
    {
        $sqlState = $e->errorInfo[0] ?? null;
        $driverCode = $e->errorInfo[1] ?? null;

        return $sqlState === '23000' && (int) $driverCode === 1062;
    }

    private static function sincronizarSerieConMax(int $serieId): void
    {
        DB::transaction(function () use ($serieId) {
            $serieRow = SeriesFacturas::where('id', $serieId)->lockForUpdate()->first();
            if (! $serieRow) {
                return;
            }

            $maxFolio = Facturas::where('team_id', $serieRow->team_id)
                ->where('serie', $serieRow->serie)
                ->max('folio');

            if ($maxFolio !== null && (int) $maxFolio > (int) $serieRow->folio) {
                $serieRow->update(['folio' => (int) $maxFolio]);
            }
        });
    }
}
