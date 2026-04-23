<?php

namespace App\Filament\Widgets;

use App\Models\CashierDeposit;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsReportCashierDepositOverview extends StatsOverviewWidget
{

    protected int | string | array $columnSpan = 2;

    protected int | string | array $columnStart = [
        'default' => 1,
    ];
    protected function getHeading(): string
    {
        return 'Setoran ke Brankas';
    }
    protected function getStats(): array
    {
        $today = Carbon::today();
        $lastMonthSameDay = $today->copy()->subMonthNoOverflow();

        // Hitung telat hari ini
        $lateToday = CashierDeposit::query()
            ->whereDate('date_published', $today)
            ->whereRaw("TIME(created_at) > '19:00:00'") // contoh aturan telat
            ->count();

        // Hitung telat bulan lalu (tanggal sama)
        $lateLastMonth = CashierDeposit::query()
            ->whereDate('date_published', $lastMonthSameDay)
            ->whereRaw("TIME(created_at) > '19:00:00'")
            ->count();

        return [
            Stat::make('Telat', $lateToday)
                ->description(($lateToday - $lateLastMonth) . ' dibanding bulan lalu')
                ->descriptionIcon($lateToday > $lateLastMonth
                    ? 'heroicon-m-arrow-trending-up'
                    : 'heroicon-m-arrow-trending-down')
                ->color($lateToday > $lateLastMonth ? 'danger' : 'success'),

        ];
    }
}
