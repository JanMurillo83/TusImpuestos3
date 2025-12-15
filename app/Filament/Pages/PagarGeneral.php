<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class PagarGeneral extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.pagar-general';
    protected static bool $shouldRegisterNavigation = false;
}
