<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class CobrarDetalle extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.cobrar-detalle';
    protected static bool $shouldRegisterNavigation = false;
}
