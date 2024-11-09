<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class Emitcfdi extends Cluster
{
    protected static ?string $title = 'Registro de CFDI Emitidos';
    protected static ?string $navigationIcon = 'fas-share-from-square';
    protected static ?string $navigationGroup = 'Registro CFDI';
    protected static ?string $sidebarWidth = '15rem';
    protected static ?int $navigationSort = 1;
}
