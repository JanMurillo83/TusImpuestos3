<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Claves extends Model
{
    protected $fillable = ['clave','descripcion','mostrar'];

    /**
     * Obtiene opciones de claves SAT con caché optimizado
     *
     * @param string $search Término de búsqueda
     * @param int $limit Límite de resultados
     * @return array
     */
    public static function getCachedOptions(string $search = '', int $limit = 50): array
    {
        // Si la búsqueda está vacía, retornar array vacío para evitar carga innecesaria
        if (empty(trim($search))) {
            return [];
        }

        // Crear clave de caché única por búsqueda
        $cacheKey = 'claves_sat_' . md5($search . '_' . $limit);

        // Cachear por 24 horas (86400 segundos)
        return Cache::remember($cacheKey, 86400, function () use ($search, $limit) {
            $rows = self::where('mostrar', 'like', "%{$search}%")
                ->limit($limit)
                ->pluck('mostrar', 'clave')
                ->toArray();

            $clean = [];
            foreach ($rows as $clave => $mostrar) {
                $clean[$clave] = self::sanitizeLabel($mostrar);
            }

            return $clean;
        });
    }

    /**
     * Limpia toda la caché de claves SAT
     * Útil cuando se actualizan las claves
     */
    public static function clearCache(): void
    {
        Cache::forget('claves_sat_*');
    }

    /**
     * Obtiene una clave específica por su código
     */
    public static function getByClave(string $clave): ?self
    {
        $cacheKey = 'clave_sat_' . $clave;

        return Cache::remember($cacheKey, 86400, function () use ($clave) {
            return self::where('clave', $clave)->first();
        });
    }

    private static function sanitizeLabel(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        $value = (string) $value;
        if (function_exists('mb_check_encoding') && mb_check_encoding($value, 'UTF-8')) {
            return $value;
        }

        if (function_exists('mb_detect_encoding') && function_exists('mb_convert_encoding')) {
            $encoding = mb_detect_encoding($value, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
            if ($encoding) {
                return mb_convert_encoding($value, 'UTF-8', $encoding);
            }
        }

        if (function_exists('iconv')) {
            $converted = iconv('UTF-8', 'UTF-8//IGNORE', $value);
            if ($converted !== false) {
                return $converted;
            }
        }

        return $value;
    }
}
