<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasDashboardFilters;
use App\Models\cashier_takeout_money;
use App\Models\CashierDeposit;
use App\Models\CashMutate;
use App\Models\CsServiceSparepart;
use App\Models\CsUnit;
use App\Models\ValidationDeposit;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class CategoryBreakdownChart extends ChartWidget
{
    use HasDashboardFilters;
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected ?string $heading = 'Distribusi Transaksi';

    protected int|string|array $columnSpan = [
        'default' => 1,
        'lg' => 4,
    ];

    protected function getCachedData(): array
    {
        $range = $this->dashboardDateRange();

        return cache()->remember($this->dashboardCacheKey('breakdown', $range), now()->addMinutes(3), function () use ($range) {
            return [
                'brankas' => $this->applyDashboardFilters(CashierDeposit::query(), $range)->count(),
                'mutasi' => $this->applyDashboardFilters(CashMutate::query(), $range)->count(),
                'validasi' => $this->applyDashboardFilters(ValidationDeposit::query(), $range)->count(),
                'cs' => $this->applyDashboardFilters(CsServiceSparepart::query(), $range)->count()
                    + $this->applyDashboardFilters(CsUnit::query(), $range)->count(),
                'takeout' => $this->applyDashboardFilters(cashier_takeout_money::query(), $range)->count(),
            ];
        });
    }

    protected function getData(): array
    {
        $d = $this->getCachedData();
        $total = $d['brankas'] + $d['mutasi'] + $d['validasi'] + $d['cs'] + $d['takeout'];

        if ($total === 0) {
            return [
                'labels' => ['Belum Ada Transaksi'],
                'datasets' => [
                    [
                        'data' => [1],
                        'backgroundColor' => ['#334155'],
                        'borderColor' => '#0f172a',
                        'borderWidth' => 2,
                    ],
                ],
            ];
        }

        return [
            'labels' => ['Brankas', 'Mutasi', 'Validasi', 'Counter Service', 'Takeout'],
            'datasets' => [
                [
                    'data' => [$d['brankas'], $d['mutasi'], $d['validasi'], $d['cs'], $d['takeout']],
                    'backgroundColor' => [
                        '#38bdf8',
                        '#a78bfa',
                        '#34d399',
                        '#fbbf24',
                        '#fb7185',
                    ],
                    'borderColor' => '#0f172a',
                    'borderWidth' => 2,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => [
                        'boxWidth' => 12,
                        'padding' => 15,
                        'font' => [
                            'size' => 11,
                        ],
                    ],
                ],
            ],
            'cutout' => '65%',
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }
}
