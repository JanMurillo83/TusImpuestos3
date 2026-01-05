<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;
use Filament\Navigation\NavigationGroup;

class tiadmin extends Cluster
{
    protected static ?string $navigationIcon = 'fas-toolbox';
    protected static ?string $title = 'Administración';
    protected static ?int $navigationSort = 2;

    public static function getNavigationGroups(): array
    {
        return [
            NavigationGroup::make()->label('Ventas'),
            NavigationGroup::make()->label('Compras'),
            NavigationGroup::make()->label('Inventario'),
            NavigationGroup::make()->label('Reportes'),
            NavigationGroup::make()->label('Configuracion'),
        ];
    }
}
