<?php

namespace App\Filament\Clusters\Herramientas\Pages;

use App\Filament\Clusters\Herramientas;
use Filament\Pages\Page;

class Tools extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.clusters.herramientas.pages.tools';

    protected static ?string $cluster = Herramientas::class;
}
