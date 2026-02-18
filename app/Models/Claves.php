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
            return self::where('mostrar', 'like', "%{$search}%")
                ->limit($limit)
                ->pluck('mostrar', 'clave')
                ->toArray();
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
}
