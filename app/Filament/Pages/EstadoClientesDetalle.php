<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class EstadoClientesDetalle extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.estado-clientes-detalle';
    protected static bool $shouldRegisterNavigation = false;
}
