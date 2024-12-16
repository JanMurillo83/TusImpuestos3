<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class AdmCompras extends Cluster
{
    protected static ?string $navigationIcon = 'fas-cart-shopping';
    protected static ?string $navigationGroup = 'Administracion';
    protected static ?string $title = 'Compras';
    protected static ?int $navigationSort = 2;
}
