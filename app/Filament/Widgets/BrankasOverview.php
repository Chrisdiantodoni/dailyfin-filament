<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasDashboardFilters;
use App\Models\CashierDeposit;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BrankasOverview extends StatsOverviewWidget
{
    use HasDashboardFilters;
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = [
        'default' => 1,
        'lg' => 12,
    ];

    protected ?string $heading = 'Setoran Brankas';

    protected ?string $description = 'Ringkasan setoran harian kasir ke brankas pada periode filter.';

    protected int|array|null $columns = [
        'default' => 1,
        'sm' => 2,
        'xl' => 4,
    ];

    protected function getCachedData(): array
    {
        $range = $this->dashboardDateRange();

        return cache()->remember($this->dashboardCacheKey('brankas', $range), now()->addMinutes(3), function () use ($range) {
            $summary = $this->applyDashboardFilters(CashierDeposit::query(), $range)
                ->selectRaw('
                    COUNT(*) as submit,
                    SUM(CASE WHEN status = "request" THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = "approve" THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN TIME(created_at) > "19:00:00" THEN 1 ELSE 0 END) as late
                ')
                ->first();

            return collect($summary?->getAttributes() ?? [])->map(fn ($value) => (int) $value)->all();
        });
    }

    protected function getStats(): array
    {
        $d = $this->getCachedData();

        return [
            Stat::make('Total', $d['submit'])
                ->description('Laporan masuk')
                ->descriptionColor('gray')
                ->icon('heroicon-m-inbox-arrow-down')
                ->color('primary'),
            Stat::make('Pending', $d['pending'])
                ->description('Menunggu Finance Ops')
                ->descriptionColor('gray')
                ->icon('heroicon-m-clock')
                ->color('warning'),
            Stat::make('Disetujui', $d['approved'])
                ->description('Sudah dikonfirmasi')
                ->descriptionColor('gray')
                ->icon('heroicon-m-check-badge')
                ->color('success'),
            Stat::make('Telat', $d['late'])
                ->description('Lewat batas 19:00')
                ->descriptionColor('gray')
                ->icon('heroicon-m-exclamation-triangle')
                ->color('danger'),
        ];
    }
}
