<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class Emitcfdi extends Cluster
{
    protected static ?string $title = 'CFDI Emitidos';
    protected static ?string $navigationIcon = 'fas-share-from-square';
    protected static ?string $navigationGroup = 'CFDI';
    protected static ?string $sidebarWidth = '15rem';
    protected static ?int $navigationSort = 1;
}
