<?php

namespace App\Filament\Clusters\tiadmin\Pages;

use App\Filament\Clusters\tiadmin;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Page;

class ComercialDashboard extends Page
{
    protected static ?string $navigationIcon = 'fas-chart-line';
    protected static ?string $cluster = tiadmin::class;
    protected static ?string $navigationLabel = 'Comercial';
    protected static ?int $navigationSort = 1;
    protected static ?string $slug = 'comercial';
    protected ?string $maxContentWidth = 'full';

    protected static string $view = 'filament.clusters.tiadmin.pages.comercial-dashboard';

    public function getTitle(): string
    {
        return '';
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole(['administrador', 'contador', 'compras', 'ventas']);
    }

    public static function getNavigationItems(): array
    {
        $tenant = Filament::getTenant();
        $embedUrl = $tenant ? url("/{$tenant->id}/tiadmin/comercial-embed") : url('/tiadmin/comercial-embed');

        return [
            NavigationItem::make(static::getNavigationLabel())
                ->icon(static::getNavigationIcon())
                ->group(static::getNavigationGroup())
                ->sort(static::getNavigationSort())
                ->url($embedUrl)
                ->openUrlInNewTab(),
        ];
    }

    public function mount(): void
    {
        $tenant = Filament::getTenant();
        $embedUrl = $tenant ? url("/{$tenant->id}/tiadmin/comercial-embed") : url('/tiadmin/comercial-embed');
        $this->redirect($embedUrl);
    }

    public function getViewData(): array
    {
        $tenant = Filament::getTenant();
        $embedUrl = $tenant ? url("/{$tenant->id}/tiadmin/comercial-embed") : url('/tiadmin/comercial-embed');

        return [
            'embed_url' => $embedUrl,
        ];
    }
}
