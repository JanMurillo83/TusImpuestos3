<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Filament\Pages\Tenancy\RegisterTeam;
use App\Http\Middleware\ApplyTenantScopes;
use App\Models\Team;
use Filament\Navigation\MenuItem;
use App\Filament\Pages\Paginas\CamPer;
use App\Filament\Pages\Registrocfdi;
use App\Filament\Resources\AlmacencfdisResource;
use App\Filament\Resources\AlmacencfdisResource\Pages\CfdiRec;
use App\Filament\Resources\AlmacencfdisResource\Pages\ListAlmacencfdis;
use Dotenv\Util\Str;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Filament\Pages\Page;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('main')
            ->login()
            ->font('Karla')
            ->favicon(asset('images/LogoCortoTR.png'))
            ->brandName(name:"Tus Impuestos")
            ->brandLogo(asset('images/MainLogoTR.png'))
            ->darkModeBrandLogo(asset('images/MainLogoTR.png'))
            ->brandLogoHeight('5rem')
            ->colors([
                'primary' => Color::Blue,
                'gray' => Color::Sky
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,

            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class
            ])
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->tenantRegistration(RegisterTeam::class)
            ->maxContentWidth('full')
            ->tenant(Team::class)
            ->tenantMiddleware([
                ApplyTenantScopes::class,
            ], isPersistent: true)
            ->tenantMenuItems([
                MenuItem::make()->label('Cambio de Periodo')
                ->url(fn (): string => CamPer::getUrl())
                ->icon('fas-calendar-check'),
            ])->sidebarFullyCollapsibleOnDesktop()
            ->sidebarWidth('18rem');
    }
}
