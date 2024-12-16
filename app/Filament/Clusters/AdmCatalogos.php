<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class AdmCatalogos extends Cluster
{
    protected static ?string $navigationIcon = 'fas-list';
    protected static ?string $navigationGroup = 'Administracion';
    protected static ?string $title = 'Catalogos';
    protected static ?int $navigationSort = 1;
}
