<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsReportValidationDepositOverview extends StatsOverviewWidget
{


    protected int | string | array $columnSpan = 1;

    protected int | string | array $columnStart = [
        'default' => 1,
    ];

    protected function getHeading(): string
    {
        return 'Validasi Setoran';
    }


    protected function getStats(): array
    {
        return [
            Stat::make('Telat', '192.1k')
                ->description('32k increase')
                ->descriptionIcon('heroicon-m-arrow-trending-up'),
            Stat::make('Telat Submit', '21%')
                ->description('32k increase')
                ->descriptionIcon('heroicon-m-arrow-trending-up'),

        ];
    }
}
