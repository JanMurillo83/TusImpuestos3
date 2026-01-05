<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\SubNavigationPosition;

class tiadmin extends Cluster
{
    protected static ?string $navigationIcon = 'fas-toolbox';
    protected static ?string $title = 'Administración';
    protected static ?int $navigationSort = 2;
    public static function getNavigationGroups(): array
    {
        return [
            NavigationGroup::make('Ventas')->label('Ventas')
            ->collapsible(true),
            NavigationGroup::make('Compras')->label('Compras'),
            NavigationGroup::make('Inventario')->label('Inventario'),
            NavigationGroup::make('Reportes')->label('Reportes'),
            NavigationGroup::make('Configuracion')->label('Configuracion'),
        ];
    }
}
