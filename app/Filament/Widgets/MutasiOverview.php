<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasDashboardFilters;
use App\Models\CashMutate;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MutasiOverview extends StatsOverviewWidget
{
    use HasDashboardFilters;
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = [
        'default' => 1,
        'lg' => 12,
    ];

    protected ?string $heading = 'Mutasi Kas';

    protected ?string $description = 'Pantauan mutasi kas dan potensi keterlambatan input.';

    protected int|array|null $columns = [
        'default' => 1,
        'sm' => 2,
        'xl' => 4,
    ];

    protected function getCachedData(): array
    {
        $range = $this->dashboardDateRange();
        $previousRange = [$range[0]->copy()->subMonthNoOverflow(), $range[1]->copy()->subMonthNoOverflow()];

        return cache()->remember($this->dashboardCacheKey('mutasi', $range), now()->addMinutes(3), function () use ($range, $previousRange) {
            $summary = $this->applyDashboardFilters(CashMutate::query(), $range)
                ->selectRaw('
                    COUNT(*) as submit,
                    SUM(CASE WHEN TIME(created_at) > "19:00:00" THEN 1 ELSE 0 END) as late
                ')
                ->first();

            $lateLast = $this->applyDashboardFilters(CashMutate::query(), $previousRange)
                ->whereRaw('TIME(created_at) > "19:00:00"')
                ->count();

            return [
                'submit' => (int) ($summary->submit ?? 0),
                'late' => (int) ($summary->late ?? 0),
                'late_last' => $lateLast,
            ];
        });
    }

    protected function getStats(): array
    {
        $d = $this->getCachedData();

        return [
            Stat::make('Total', $d['submit'])
                ->description('Mutasi dibuat')
                ->descriptionColor('gray')
                ->icon('heroicon-m-arrows-right-left')
                ->color('primary'),
            Stat::make('Telat', $d['late'])
                ->description(($d['late'] - $d['late_last']).' vs bulan lalu')
                ->descriptionColor('gray')
                ->descriptionIcon($d['late'] > $d['late_last'] ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($d['late'] > $d['late_last'] ? 'danger' : 'success'),
        ];
    }
}
