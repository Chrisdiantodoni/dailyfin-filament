<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasDashboardFilters;
use App\Models\ValidationDeposit;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ValidasiOverview extends StatsOverviewWidget
{
    use HasDashboardFilters;
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = [
        'default' => 1,
        'lg' => 12,
    ];

    protected ?string $heading = 'Validasi Setoran';

    protected ?string $description = 'Status validasi bukti setoran oleh tim finance pada periode filter.';

    protected int|array|null $columns = [
        'default' => 1,
        'sm' => 2,
        'xl' => 4,
    ];

    protected function getCachedData(): array
    {
        $range = $this->dashboardDateRange();

        return cache()->remember($this->dashboardCacheKey('validasi', $range), now()->addMinutes(3), function () use ($range) {
            $summary = $this->applyDashboardFilters(ValidationDeposit::query(), $range)
                ->selectRaw('
                    COUNT(*) as submit,
                    SUM(CASE WHEN status = "request" THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = "approve" THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN created_at > TIMESTAMPADD(HOUR, 12, DATE_ADD(date_published, INTERVAL 1 DAY)) THEN 1 ELSE 0 END) as late
                ')
                ->first();

            return [
                'submit' => (int) ($summary->submit ?? 0),
                'pending' => (int) ($summary->pending ?? 0),
                'approved' => (int) ($summary->approved ?? 0),
                'late' => (int) ($summary->late ?? 0),
            ];
        });
    }

    protected function getStats(): array
    {
        $d = $this->getCachedData();

        return [
            Stat::make('Total', $d['submit'])
                ->description('Validasi dibuat')
                ->descriptionColor('gray')
                ->icon('heroicon-m-magnifying-glass')
                ->color('primary'),
            Stat::make('Pending', $d['pending'])
                ->description('Menunggu Finance Spv')
                ->descriptionColor('gray')
                ->icon('heroicon-m-clock')
                ->color('warning'),
            Stat::make('Disetujui', $d['approved'])
                ->description('Validasi selesai')
                ->descriptionColor('gray')
                ->icon('heroicon-m-check-badge')
                ->color('success'),
            Stat::make('Telat', $d['late'])
                ->description('Lewat H+1 12:00')
                ->descriptionColor('gray')
                ->icon('heroicon-m-exclamation-triangle')
                ->color('danger'),
        ];
    }
}
