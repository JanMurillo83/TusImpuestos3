<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class EstadoProveedoresDetalle extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.estado-proveedores-detalle';
    protected static bool $shouldRegisterNavigation = false;
}
