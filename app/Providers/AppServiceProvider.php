<?php

namespace App\Providers;

use Filament\Support\Assets\Js;
use Illuminate\Support\ServiceProvider;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
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
                     ->label('Operaciones CFDI')
                     ->collapsed(),
                NavigationGroup::make()
                    ->label('Contabilidad')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Bancos')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Administracion')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Reportes')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Herramientas')
                    ->collapsed(),
            ]);
        });
        FilamentView::registerRenderHook(
            PanelsRenderHook::FOOTER,
            fn (): View => view('customFooter'),
        );
    }
}
