<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class AdmReportes extends Cluster
{
    protected static ?string $navigationGroup = 'Administracion';
    protected static ?string $title = 'Reportes';
    protected static ?string $navigationIcon = 'fas-print';
    protected static ?int $navigationSort = 6;
}
