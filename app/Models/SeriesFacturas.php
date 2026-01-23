<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class SeriesFacturas extends Model
{
    protected $fillable = ['serie','descripcion', 'tipo', 'folio','team_id'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Obtiene el siguiente folio disponible de forma segura usando locks
     *
     * @param int $serieId ID de la serie de facturación
     * @return array ['serie' => string, 'folio' => int, 'docto' => string]
     * @throws \Exception Si no se encuentra la serie
     */
    public static function obtenerSiguienteFolio(int $serieId): array
    {
        return DB::transaction(function () use ($serieId) {
            // Obtener la serie con lock para evitar condiciones de carrera
            $serieRow = self::where('id', $serieId)
                ->lockForUpdate()
                ->first();

            if (!$serieRow) {
                throw new \Exception("Serie de facturación no encontrada");
            }

            $nuevoFolio = $serieRow->folio + 1;

            // Incrementar el folio en la base de datos
            $serieRow->increment('folio');

            return [
                'serie' => $serieRow->serie,
                'folio' => $nuevoFolio,
                'docto' => $serieRow->serie . $nuevoFolio,
            ];
        });
    }

    /**
     * Obtiene el siguiente folio SIN incrementarlo (para preview)
     *
     * @param int $serieId ID de la serie de facturación
     * @return array ['serie' => string, 'folio' => int, 'docto' => string]
     */
    public static function previewSiguienteFolio(int $serieId): array
    {
        $serieRow = self::find($serieId);

        if (!$serieRow) {
            throw new \Exception("Serie de facturación no encontrada");
        }

        $siguienteFolio = $serieRow->folio + 1;

        return [
            'serie' => $serieRow->serie,
            'folio' => $siguienteFolio,
            'docto' => $serieRow->serie . $siguienteFolio,
        ];
    }
}
