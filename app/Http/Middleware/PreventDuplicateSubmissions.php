<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class PreventDuplicateSubmissions
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Solo aplicar a peticiones POST, PUT, PATCH, DELETE
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return $next($request);
        }

        // Generar un hash único basado en:
        // - IP del usuario
        // - Ruta de la petición
        // - Datos del formulario (excepto el token CSRF)
        $requestData = $request->except(['_token', '_method']);
        $requestSignature = md5(
            $request->ip() .
            $request->path() .
            json_encode($requestData)
        );

        // Crear clave única para el cache
        $cacheKey = 'duplicate_submit_' . $requestSignature;

        // Verificar si esta petición ya fue procesada en los últimos 5 segundos
        if (Cache::has($cacheKey)) {
            // Retornar respuesta de duplicado
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Esta petición ya está siendo procesada. Por favor espere.',
                    'error' => 'duplicate_submission'
                ], 429);
            }

            return back()->with('warning', 'Esta acción ya está siendo procesada. Por favor espere.');
        }

        // Marcar esta petición como en proceso por 5 segundos
        Cache::put($cacheKey, true, 5);

        // Continuar con la petición
        $response = $next($request);

        // Si la respuesta fue exitosa, extender el tiempo de bloqueo a 10 segundos
        // para prevenir reenvíos inmediatos después de procesar
        if ($response->isSuccessful()) {
            Cache::put($cacheKey, true, 10);
        } else {
            // Si falló, eliminar el bloqueo para permitir reintento
            Cache::forget($cacheKey);
        }

        return $response;
    }
}
