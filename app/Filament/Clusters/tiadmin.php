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
            ->collapsible(true)
            ->collapsed(),
            NavigationGroup::make('Compras')->label('Compras')
            ->collapsible(true)
            ->collapsed(),
            NavigationGroup::make('Inventario')->label('Inventario')
            ->collapsible(true)
            ->collapsed(),
            NavigationGroup::make('Reportes')->label('Reportes')
            ->collapsible(true)
            ->collapsed(),
            NavigationGroup::make('Configuracion')->label('Configuracion')
            ->collapsible(true)
            ->collapsed(),
        ];
    }
}
