<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class AdmConfiguracion extends Cluster
{
    protected static ?string $navigationGroup = 'Administracion';
    protected static ?string $title = 'Configuracion';
    protected static ?string $navigationIcon = 'fas-screwdriver-wrench';
    protected static ?int $navigationSort = 5;
}
