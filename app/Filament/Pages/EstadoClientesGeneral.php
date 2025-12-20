<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class EstadoClientesGeneral extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.estado-clientes-general';
    protected static bool $shouldRegisterNavigation = false;
    protected ?string $maxContentWidth = 'full';
    public function getTitle(): string
    {
        return '';
    }
}
