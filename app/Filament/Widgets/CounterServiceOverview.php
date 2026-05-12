<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasDashboardFilters;
use App\Models\CsServiceSparepart;
use App\Models\CsUnit;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CounterServiceOverview extends StatsOverviewWidget
{
    use HasDashboardFilters;
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = [
        'default' => 1,
        'lg' => 4,
    ];

    protected ?string $heading = 'Counter Service';

    protected int|array|null $columns = 2;

    protected function getCachedData(): array
    {
        $range = $this->dashboardDateRange();

        return cache()->remember($this->dashboardCacheKey('counter-service', $range), now()->addMinutes(3), function () use ($range) {
            $sparepart = $this->applyDashboardFilters(CsServiceSparepart::query(), $range)
                ->selectRaw('
                    COUNT(*) as submit,
                    SUM(CASE WHEN status = "request" THEN 1 ELSE 0 END) as pending
                ')
                ->first();

            $unit = $this->applyDashboardFilters(CsUnit::query(), $range)
                ->selectRaw('
                    COUNT(*) as submit,
                    SUM(CASE WHEN status = "request" THEN 1 ELSE 0 END) as pending
                ')
                ->first();

            return [
                'sparepart' => (int) ($sparepart->submit ?? 0),
                'unit' => (int) ($unit->submit ?? 0),
                'pending_sparepart' => (int) ($sparepart->pending ?? 0),
                'pending_unit' => (int) ($unit->pending ?? 0),
            ];
        });
    }

    protected function getStats(): array
    {
        $d = $this->getCachedData();

        return [
            Stat::make('Sparepart', $d['sparepart'])->icon('heroicon-m-wrench')->color('info'),
            Stat::make('Unit', $d['unit'])->icon('heroicon-m-truck')->color('info'),
            Stat::make('Pending Sparepart', $d['pending_sparepart'])->icon('heroicon-m-clock')->color('warning'),
            Stat::make('Pending Unit', $d['pending_unit'])->icon('heroicon-m-clock')->color('warning'),
        ];
    }
}
