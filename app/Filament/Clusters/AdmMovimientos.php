<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class AdmMovimientos extends Cluster
{
    protected static ?string $navigationGroup = 'Administracion';
    protected static ?string $title = 'Movimientos';
    protected static ?string $navigationIcon = 'fas-truck-ramp-box';
    protected static ?int $navigationSort = 4;
}
