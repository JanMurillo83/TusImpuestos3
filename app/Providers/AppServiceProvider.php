<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;

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
                     ->icon('fas-laptop-file')
                     ->collapsed(),
                NavigationGroup::make()
                    ->label('Contabilidad')
                    ->icon('fas-scale-balanced')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Facturacion')
                    ->icon('fas-file-invoice-dollar')
                    ->collapsed(),
            ]);
        });
    }
}
