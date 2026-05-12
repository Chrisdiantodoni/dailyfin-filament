<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasDashboardFilters;
use App\Models\cashier_takeout_money;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TakeoutOverview extends StatsOverviewWidget
{
    use HasDashboardFilters;
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = [
        'default' => 1,
        'lg' => 4,
    ];

    protected ?string $heading = 'Pengambilan Uang';

    protected int|array|null $columns = 2;

    protected function getCachedData(): array
    {
        $range = $this->dashboardDateRange();

        return cache()->remember($this->dashboardCacheKey('takeout', $range), now()->addMinutes(3), function () use ($range) {
            $summary = $this->applyDashboardFilters(cashier_takeout_money::query(), $range)
                ->selectRaw('
                    COUNT(*) as submit,
                    SUM(CASE WHEN status = "request" THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = "approve" THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN status = "approve" THEN takeout_nominal ELSE 0 END) as nominal
                ')
                ->first();

            return [
                'submit' => (int) ($summary->submit ?? 0),
                'pending' => (int) ($summary->pending ?? 0),
                'approved' => (int) ($summary->approved ?? 0),
                'nominal' => (int) ($summary->nominal ?? 0),
            ];
        });
    }

    protected function getStats(): array
    {
        $d = $this->getCachedData();

        return [
            Stat::make('Submit', $d['submit'])->icon('heroicon-m-arrow-up-tray')->color('primary'),
            Stat::make('Pending', $d['pending'])->icon('heroicon-m-clock')->color('warning'),
            Stat::make('Disetujui', $d['approved'])->icon('heroicon-m-check-badge')->color('success'),
            Stat::make('Nominal', 'Rp '.number_format($d['nominal'], 0, ',', '.'))->icon('heroicon-m-banknotes')->color('success'),
        ];
    }
}
