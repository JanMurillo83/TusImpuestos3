<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class Operscfdi extends Cluster
{
    protected static ?string $title = 'Operaciones CFDI';
    protected static ?string $navigationIcon = 'fas-receipt';
    protected static ?string $navigationGroup = 'CFDI';
    protected static ?string $sidebarWidth = '15rem';
    protected static ?int $navigationSort = 3;
}
