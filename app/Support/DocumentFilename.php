<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Str;

class DocumentFilename
{
    public static function build(string $tipo, ?string $docto, ?string $nombre, $fecha): string
    {
        $tipo = self::sanitizeSegment($tipo);
        $docto = self::sanitizeSegment($docto ?: 'SIN_DOCTO');
        $nombre = self::sanitizeSegment($nombre ?: 'SIN_NOMBRE');

        $fechaStr = null;
        if ($fecha instanceof Carbon) {
            $fechaStr = $fecha->format('dmY');
        } elseif (is_string($fecha) && trim($fecha) !== '') {
            $fechaStr = Carbon::parse($fecha)->format('dmY');
        }

        if (! $fechaStr) {
            $fechaStr = Carbon::now()->format('dmY');
        }

        return $tipo . '_' . $docto . '_' . $nombre . '_' . $fechaStr . '.pdf';
    }

    private static function sanitizeSegment(string $value): string
    {
        $slug = Str::slug($value, '_');
        $slug = trim($slug, '_');
        $slug = preg_replace('/_+/', '_', $slug) ?: 'SIN_DATO';

        return Str::upper($slug);
    }
}
