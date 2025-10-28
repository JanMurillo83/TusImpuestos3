<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class Nominas extends Cluster
{
    protected static ?string $title = 'Nominas';
    protected static ?string $navigationIcon = 'fas-users-viewfinder';
    protected static ?int $navigationSort = 4;
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
    protected static ?string $navigationGroup = 'Administracion';
}
