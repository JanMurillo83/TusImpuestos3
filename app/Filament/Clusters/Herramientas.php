<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class Herramientas extends Cluster
{
    protected static ?string $navigationIcon = 'fas-wrench';
    protected static ?string $navigationGroup = 'Herramientas';
    protected static ?int $navigationSort = 8;
    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }
}
