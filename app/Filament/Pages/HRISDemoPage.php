<?php

namespace App\Filament\Pages;

use Filament\Facades\Filament;
use Filament\Pages\Page;

class HRISDemoPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'RH';
    protected static ?string $navigationGroup = 'Recursos Humanos';
    protected static string $view = 'filament.pages.hris-demo-launcher';
    protected ?string $maxContentWidth = 'full';

    public function getTitle(): string
    {
        return '';
    }

    public function getViewData(): array
    {
        $tenant = Filament::getTenant();
        $embedUrl = $tenant
            ? url("/{$tenant->id}/tiadmin/hris-demo-embed")
            : url('/tiadmin/hris-demo-embed');

        return [
            'embed_url' => $embedUrl,
        ];
    }
}
