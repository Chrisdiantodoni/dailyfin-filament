<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ReportStat;
use App\Filament\Widgets\StatsReportCashierDepositOverview;
use App\Filament\Widgets\StatsReportCashierMutationOverview;
use App\Filament\Widgets\StatsReportOverview;
use App\Filament\Widgets\StatsReportValidationDepositOverview;
use App\Models\Dealer;
use App\Models\DealerUser;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Actions\FilterAction;
use Filament\Pages\Dashboard\Concerns\HasFiltersAction;
use Illuminate\Support\Facades\Auth;

class Dashboard extends BaseDashboard
{
    protected string $view = 'filament.pages.dashboard';

    use HasFiltersAction;

    protected function getHeaderActions(): array
    {
        return [
            FilterAction::make()
                ->schema([
                    DatePicker::make('startDate'),
                    DatePicker::make('endDate'),
                    Select::make('dealer_code')
                        ->label('Dealer')
                        ->options(
                            Dealer::whereIn(
                                'dealer_code',
                                DealerUser::where('user_id', Auth::id())->pluck('dealer_code')
                            )->pluck('dealer_name', 'dealer_code')
                        )
                        ->preload()
                        ->default(null)
                        ->searchable()
                        ->placeholder('Pilih Dealer')
                        ->hidden(fn() => Auth::user()->dealer_users->count() === 1)
                        ->live(),
                ]),
        ];
    }

    public function getHeaderWidgets(): array
    {
        return [
            StatsReportCashierDepositOverview::class,
            StatsReportCashierMutationOverview::class,
            // StatsReportValidationDepositOverview::class,
        ];
    }

    public function getHeaderWidgetsColumns(): array|int
    {
        return 1;
    }
    protected function getGridColumns(): int
    {
        return 3;
    }
}
