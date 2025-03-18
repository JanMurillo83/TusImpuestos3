<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class Nominas extends Cluster
{
    protected static ?string $title = 'Nominas';
    protected static ?string $navigationIcon = 'fas-users-viewfinder';
    protected static ?string $navigationGroup = 'Registro CFDI';
    protected static ?int $navigationSort = 4;
}
