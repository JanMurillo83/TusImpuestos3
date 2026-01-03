<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class tiadmin extends Cluster
{
    protected static ?string $navigationIcon = 'fas-cash-register';
    protected static ?string $title = 'Administración';
    protected static ?int $navigationSort = 2;
}
