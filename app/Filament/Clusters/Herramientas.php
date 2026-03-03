<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class Herramientas extends Cluster
{
    protected static ?string $navigationIcon = 'fas-wrench';
    protected static ?string $navigationGroup = 'Herramientas';
    protected static ?int $navigationSort = 8;
    public static function shouldRegisterNavigation () : bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        // Fallbacks: algunos usuarios pueden tener `is_admin` o `role` sin estar ligados en la tabla pivote.
        if (! empty($user->is_admin)) {
            return true;
        }

        if (($user->role ?? null) && in_array($user->role, ['administrador', 'admin'], true)) {
            return true;
        }

        return method_exists($user, 'hasRole')
            ? $user->hasRole(['administrador', 'admin'])
            : false;
    }
}
