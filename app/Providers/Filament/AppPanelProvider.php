<?php

namespace App\Providers\Filament;

use App\Filament\Pages\CoordinatorDetail;
use App\Filament\Resources\CashierDeposits\CashierDepositResource;
use App\Filament\Resources\CounterServiceDeposits\CounterServiceDepositResource;
use App\Filament\Resources\CounterServiceUnits\CounterServiceUnitResource;
use App\Filament\Resources\Users\UsersResource;
use App\Models\CashierDeposit;
use App\Models\CsServiceSparepart;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use function Filament\Support\original_request;

class AppPanelProvider extends PanelProvider
{


    public function panel(Panel $panel): Panel
    {
        return $panel
            // ->default()
            ->id('app')
            ->path('app')
            ->brandName('')
            ->brandLogo(asset('assets/logo-dashboard.png'))
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->viteTheme('resources/css/filament/app/theme.css')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])->sidebarWidth(
                200,
            )->sidebarCollapsibleOnDesktop()
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
            ->navigationGroups([])
            ->authMiddleware([
                Authenticate::class,
            ])->routes(function () {
                // Register any custom routes here. 
                // The routes will be prefixed with the panel's path, e.g., '/admin/my-custom-route'.
                Route::get('/coordinator-detail/{id}', CoordinatorDetail::class);
            })
            // ->navigation(function (NavigationBuilder $builder): NavigationBuilder {
            //     return $builder
            //         ->items([
            //             NavigationItem::make('Dashboard')
            //                 ->icon('heroicon-o-home')
            //                 ->isActiveWhen(fn(): bool => original_request()->routeIs('filament.admin.pages.dashboard'))
            //                 ->url(fn(): string => Dashboard::getUrl())->sort(1),
            //         ])
            //         ->groups([
            //             NavigationGroup::make('Counter Service')
            //                 ->items([
            //                     NavigationItem::make('Counter Service Sparepart')
            //                         ->isActiveWhen(fn(): bool => original_request()->routeIs('filament.resources.counter-service-deposits.*'))
            //                         ->url(fn(): string => CounterServiceDepositResource::getUrl()),
            //                     NavigationItem::make('Counter Service Units')
            //                         ->isActiveWhen(fn(): bool => original_request()->routeIs('filament.resources.counter-service-units.*'))
            //                         ->url(fn(): string => CounterServiceUnitResource::getUrl()),
            //                 ]),
            //         ])->items([
            //             NavigationItem::make('Setoran Harian Brankas')
            //                 ->isActiveWhen(fn(): bool => original_request()->routeIs('filament.admin.pages.dashboard'))
            //                 ->url(fn(): string => CashierDepositResource::getUrl()),
            //         ]);
            // })

            ->databaseNotifications()
            // ->databaseNotificationsPolling('10s')
            ->renderHook(
                'panels::body.end',
                fn(): string => Blade::render('@vite([\'resources/js/bootstrap.js\'])'),
            )

            ->globalSearch(false);
    }
}
