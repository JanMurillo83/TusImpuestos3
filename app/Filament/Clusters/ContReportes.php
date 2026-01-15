<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class ContReportes extends Cluster
{
    protected static ?string $title = 'Reportes';
    protected static ?string $navigationIcon = 'fas-sheet-plastic';
    protected static ?string $navigationGroup = 'Reportes';
    protected static ?string $sidebarWidth = '15rem';
    protected static ?int $navigationSort = 3;

    public static function shouldRegisterNavigation () : bool
    {
        //return auth()->user()->hasRole(['administrador','contador']);
        return false;
    }
}
