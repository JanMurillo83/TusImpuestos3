<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class MainView extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.main-view';
    protected static bool $shouldRegisterNavigation = false;
}
