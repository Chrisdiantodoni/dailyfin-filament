<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasDashboardFilters;
use App\Models\CashierDeposit;
use App\Models\CashMutate;
use App\Models\ValidationDeposit;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class TransactionTrendChart extends ChartWidget
{
    use HasDashboardFilters;
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected string $view = 'filament.widgets.transaction-trend-chart';

    protected ?string $heading = 'Trend Ringkas Transaksi';

    protected ?string $description = 'Perbandingan jumlah laporan Brankas, Mutasi Kas, dan Validasi Setoran per hari. Rentang grafik otomatis dibatasi maksimal 14 hari terakhir dari filter.';

    protected int|string|array $columnSpan = [
        'default' => 1,
        'lg' => 12,
    ];

    protected ?string $maxHeight = '320px';

    protected function getCachedData(): array
    {
        $range = $this->dashboardDateRange(7);

        if ($range[0]->diffInDays($range[1]) > 13) {
            $range[0] = $range[1]->copy()->subDays(13)->startOfDay();
        }

        return cache()->remember($this->dashboardCacheKey('trend-lite', $range), now()->addMinutes(5), function () use ($range) {
            $dates = collect($range[0]->daysUntil($range[1]))
                ->mapWithKeys(fn ($date) => [$date->toDateString() => $date->translatedFormat('d M')]);

            return [
                'dates' => $dates,
                'brankasData' => $this->dailyCounts(CashierDeposit::class, $range, $dates->keys()->all()),
                'mutasiData' => $this->dailyCounts(CashMutate::class, $range, $dates->keys()->all()),
                'validasiData' => $this->dailyCounts(ValidationDeposit::class, $range, $dates->keys()->all()),
            ];
        });
    }

    protected function dailyCounts(string $modelClass, array $range, array $dateKeys): array
    {
        $rows = $this->applyDashboardFilters($modelClass::query(), $range)
            ->selectRaw('date_published as published_date, COUNT(*) as total')
            ->groupBy('date_published')
            ->pluck('total', 'published_date');

        return collect($dateKeys)
            ->map(fn ($date) => (int) ($rows[$date] ?? 0))
            ->all();
    }

    protected function getData(): array
    {
        $d = $this->getCachedData();

        return [
            'labels' => $d['dates']->values()->toArray(),
            'datasets' => [
                [
                    'label' => 'Brankas',
                    'data' => $d['brankasData'],
                    'borderColor' => '#38bdf8',
                    'backgroundColor' => 'rgba(56, 189, 248, 0.12)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Mutasi',
                    'data' => $d['mutasiData'],
                    'borderColor' => '#a78bfa',
                    'backgroundColor' => 'rgba(167, 139, 250, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Validasi',
                    'data' => $d['validasiData'],
                    'borderColor' => '#34d399',
                    'backgroundColor' => 'rgba(52, 211, 153, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
        ];
    }

    public function hasTrendData(): bool
    {
        $d = $this->getCachedData();

        return collect([
            ...$d['brankasData'],
            ...$d['mutasiData'],
            ...$d['validasiData'],
        ])->sum() > 0;
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => [
                        'boxWidth' => 12,
                        'padding' => 10,
                        'font' => [
                            'size' => 11,
                        ],
                    ],
                ],
            ],
            'interaction' => [
                'intersect' => false,
                'mode' => 'index',
            ],
            'elements' => [
                'point' => [
                    'radius' => 3,
                    'hoverRadius' => 5,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }
}
