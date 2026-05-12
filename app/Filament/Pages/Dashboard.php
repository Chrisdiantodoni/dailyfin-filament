<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\BrankasOverview;
use App\Filament\Widgets\MutasiOverview;
use App\Filament\Widgets\TransactionTrendChart;
use App\Filament\Widgets\ValidasiOverview;
use App\Models\Dealer;
use App\Models\DealerUser;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard\Actions\FilterAction;
use Filament\Pages\Dashboard as BaseDashboard;
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
                    DatePicker::make('startDate')
                        ->label('Dari Tanggal')
                        ->placeholder('Hari ini'),
                    DatePicker::make('endDate')
                        ->label('Sampai Tanggal')
                        ->placeholder('Hari ini'),
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
                        ->hidden(fn () => Auth::user()->dealer_users->count() === 1)
                        ->live(),
                ]),
        ];
    }

    public function getHeaderWidgets(): array
    {
        return [
            BrankasOverview::class,
            ValidasiOverview::class,
            MutasiOverview::class,
            TransactionTrendChart::class,
        ];
    }

    public function getFooterWidgets(): array
    {
        return [];
    }

    public function getHeaderWidgetsColumns(): array|int
    {
        return [
            'default' => 1,
            'lg' => 12,
        ];
    }

    public function getFooterWidgetsColumns(): array|int
    {
        return [
            'default' => 1,
            'lg' => 12,
        ];
    }
}
