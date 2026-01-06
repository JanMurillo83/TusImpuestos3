<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class Viscfdi extends Cluster
{
    protected static ?string $title = 'Visor CFDI Emitidos';
    protected static ?string $navigationIcon = 'fas-file-invoice';
    protected static ?string $navigationGroup = 'Operaciones CFDI';
    protected static ?string $sidebarWidth = '15rem';
    protected static ?int $navigationSort = 4;
    public static function shouldRegisterNavigation () : bool
    {
        return auth()->user()->hasRole(['administrador','contador']);
    }
}
