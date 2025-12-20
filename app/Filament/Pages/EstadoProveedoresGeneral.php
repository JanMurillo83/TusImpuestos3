<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class EstadoProveedoresGeneral extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.estado-proveedores-general';
    protected static bool $shouldRegisterNavigation = false;
}
