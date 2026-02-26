<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ResetTablePaginationPerPage
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($request->hasSession()) {
            $session = $request->session();
            $keys = array_keys($session->all());

            foreach ($keys as $key) {
                if (str_starts_with($key, 'tables.') && str_ends_with($key, '_per_page')) {
                    $session->forget($key);
                }
            }
        }

        return $response;
    }
}
