<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\View\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
         Filament::serving(function () {
            Filament::registerNavigationGroups([
                NavigationGroup::make()
                    ->label('Descargas CFDI')
                    ->icon('fas-download')
                    ->collapsed(),
                NavigationGroup::make()
                     ->label('CFDI')
                     ->collapsed(),
                NavigationGroup::make()
                    ->label('Contabilidad')
                    ->icon('fas-scale-balanced')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Bancos')
                    ->icon('fas-building-columns')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Facturacion')
                    ->icon('fas-file-invoice-dollar')
                    ->collapsed(),
            ]);
        });
        FilamentView::registerRenderHook(
            PanelsRenderHook::SIDEBAR_NAV_START,
            fn (): View => view('periodoview'),
        );
    }
}
