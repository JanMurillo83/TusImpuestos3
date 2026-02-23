<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;

class RestrictDefaultTenant
{
    public function handle(Request $request, Closure $next)
    {
        $tenant = Filament::getTenant();

        if ($tenant && $tenant->id === 1) {
            // Obtener el primer tenant válido (diferente de id=1) del usuario
            $user = auth()->user();
            if ($user) {
                $otherTenant = $user->teams()->where('teams.id', '!=', 1)->first();
                if ($otherTenant) {
                    $panel = Filament::getCurrentPanel();
                    $url = $panel->getUrl($otherTenant);
                    return redirect($url);
                }
            }
            // Si no tiene otros tenants, redirigir a registro de tenant
            return redirect('/new');
        }

        return $next($request);
    }
}
