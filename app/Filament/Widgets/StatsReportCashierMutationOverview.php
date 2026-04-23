<?php

namespace App\Filament\Widgets;

use App\Models\CashMutate;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsReportCashierMutationOverview extends StatsOverviewWidget
{
    protected int | string | array $columnSpan = 1;

    protected int | string | array $columnStart = [
        'default' => 1,
    ];

    protected function getHeading(): string
    {
        return 'Mutasi Kas';
    }
    protected function getStats(): array
    {
        $today = Carbon::today();
        $lastMonthSameDay = $today->copy()->subMonthNoOverflow();

        // Hitung telat hari ini
        $lateToday = CashMutate::query()
            ->whereDate('date_published', $today)
            ->whereRaw("TIME(created_at) > '19:00:00'") // contoh aturan telat
            ->count();

        // Hitung telat bulan lalu (tanggal sama)
        $lateLastMonth = CashMutate::query()
            ->whereDate('date_published', $lastMonthSameDay)
            ->whereRaw("TIME(created_at) > '19:00:00'")
            ->count();

        // Hitung semua submit hari ini
        $submitToday = CashMutate::query()
            ->whereDate('date_published', $today)
            ->count();

        // Hitung semua submit bulan lalu (tanggal sama)
        $submitLastMonth = CashMutate::query()
            ->whereDate('date_published', $lastMonthSameDay)
            ->count();

        return [
            Stat::make('Telat', $lateToday)
                ->description(($lateToday - $lateLastMonth) . ' dibanding bulan lalu')
                ->descriptionIcon($lateToday > $lateLastMonth
                    ? 'heroicon-m-arrow-trending-up'
                    : 'heroicon-m-arrow-trending-down')
                ->color($lateToday > $lateLastMonth ? 'danger' : 'success'),

            Stat::make('Total Submit', $submitToday)
                ->description(($submitToday - $submitLastMonth) . ' dibanding bulan lalu')
                ->descriptionIcon($submitToday > $submitLastMonth
                    ? 'heroicon-m-arrow-trending-up'
                    : 'heroicon-m-arrow-trending-down')
                ->color($submitToday > $submitLastMonth ? 'success' : 'danger'),
        ];
    }
}
