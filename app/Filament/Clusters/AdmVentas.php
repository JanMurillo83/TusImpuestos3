<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class AdmVentas extends Cluster
{
    protected static ?string $navigationIcon = 'fas-cash-register';
    protected static ?string $navigationGroup = 'Administracion';
    protected static ?string $title = 'Ventas';
    protected static ?int $navigationSort = 2;
}
