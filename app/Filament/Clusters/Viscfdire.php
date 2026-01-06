<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class Viscfdire extends Cluster
{
    protected static ?string $title = 'Visor CFDI Recibidos';
    protected static ?string $navigationIcon = 'fas-receipt';
    protected static ?string $navigationGroup = 'Operaciones CFDI';
    protected static ?string $sidebarWidth = '15rem';
    protected static ?int $navigationSort = 5;
    public static function shouldRegisterNavigation () : bool
    {
        return auth()->user()->hasRole(['administrador','contador']);
    }
}
