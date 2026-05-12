<?php

namespace App\Providers\Filament;

use App\Filament\Resources\CashierDeposits\CashierDepositResource;
use App\Filament\Resources\CounterServiceDeposits\CounterServiceDepositResource;
use App\Filament\Resources\CounterServiceUnits\CounterServiceUnitResource;
use App\Filament\Resources\Users\UsersResource;
use App\Http\Middleware\PasswordResetChecker;
use App\Http\Middleware\PreventCashierDeposit;
use App\Http\Middleware\PreventCashMutates;
use App\Http\Middleware\PreventRequestsDuringMaintenance;
use App\Http\Middleware\PreventTakeoutMoney;
use App\Livewire\PasswordResetModal;
use App\Models\CashierDeposit;
use App\Models\CsServiceSparepart;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Filament\View\PanelsIconAlias;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Yebor974\Filament\RenewPassword\RenewPasswordPlugin;

use function Filament\Support\original_request;

class AppPanelProvider extends PanelProvider
{


    public function panel(Panel $panel): Panel
    {
        return $panel
            // ->default()
            ->id('app')
            ->path('app')
            ->brandName(config('app.name', 'Daily Finance'))
            ->brandLogo(new HtmlString(sprintf(
                '<div class="df-brand-logo"><img src="%s" alt="Logo Daily Finance"><span>Daily Finance</span></div>',
                asset('assets/logo-dashboard.png'),
            )))
            ->brandLogoHeight('2rem')
            ->login()
            ->icons([
                PanelsIconAlias::SIDEBAR_EXPAND_BUTTON => Heroicon::OutlinedBars3BottomLeft,
                PanelsIconAlias::SIDEBAR_EXPAND_BUTTON_RTL => Heroicon::OutlinedBars3BottomLeft,
                PanelsIconAlias::SIDEBAR_COLLAPSE_BUTTON => Heroicon::OutlinedBars3BottomLeft,
                PanelsIconAlias::SIDEBAR_COLLAPSE_BUTTON_RTL => Heroicon::OutlinedBars3BottomLeft,
            ])
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                // Dashboard::class,
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
                // PasswordResetChecker::class,
                // PreventCashierDeposit::class,
                // PreventCashMutates::class,
                // PreventRequestsDuringMaintenance::class,
                // PreventTakeoutMoney::class,

            ])
            ->navigationGroups([
                NavigationGroup::make('Setoran ke Kasir')
                    ->icon('heroicon-o-currency-dollar')
                    ->collapsed(),
                NavigationGroup::make('Setoran ke Bank')
                    ->icon('heroicon-o-building-library')
                    ->collapsed(),
                NavigationGroup::make('Laporan Kas')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->collapsed(),
                NavigationGroup::make('Master Data')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->collapsed(),
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
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
            ->plugin(
                RenewPasswordPlugin::make()
                    ->forceRenewPassword()
                    ->timestampColumn('is_password_changed')
            )
            ->globalSearch(false);
    }
}
