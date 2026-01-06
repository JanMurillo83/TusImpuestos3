<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class Rececfdi extends Cluster
{
    protected static ?string $title = 'Registro de CFDI Recibidos';
    protected static ?string $navigationIcon = 'fas-diagram-predecessor';
    protected static ?string $navigationGroup = 'Registro CFDI';
    protected static ?string $sidebarWidth = '15rem';
    protected static ?int $navigationSort = 2;
    public static function shouldRegisterNavigation () : bool
    {
        return auth()->user()->hasRole(['administrador','contador']);
    }
}
