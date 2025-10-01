<?php

namespace App\Filament\Resources\CounterServiceUnits\Pages;

use App\Exports\ExportUnit;
use App\Filament\Resources\CounterServiceUnits\CounterServiceUnitResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class ListCounterServiceUnits extends ListRecords
{
    protected static string $resource = CounterServiceUnitResource::class;


    protected static ?string $title = 'Daftar Setoran Unit';
    public function getBreadcrumbs(): array
    {
        return [
            'Unit',
            'Daftar Setoran Unit'
        ];
    }

    protected function getHeaderActions(): array
    {
        return [

            ActionGroup::make([
                Action::make('export_excel')
                    ->label('Excel')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(fn($livewire) => Excel::download(
                        new ExportUnit(
                            $livewire->tableFilters['date_range']['start_date'] ?? null,
                            $livewire->tableFilters['date_range']['end_date'] ?? null
                        ),
                        'Setoran Counter Unit ke Kasir dari ' .
                            ($livewire->tableFilters['date_range']['start_date'] ?? 'awal') .
                            ' s.d ' .
                            ($livewire->tableFilters['date_range']['end_date'] ?? 'akhir') .
                            '.xlsx'
                    )),

                Action::make('export_pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-text')
                    ->color('danger')
                    ->url(fn($livewire) => route('export.unit.pdf', [
                        'start_date' => $livewire->tableFilters['date_range']['start_date'] ?? null,
                        'end_date'   => $livewire->tableFilters['date_range']['end_date'] ?? null,
                    ]))
                    ->openUrlInNewTab(),
            ])->button()
                ->label('Export')
                ->icon('heroicon-o-arrow-down-tray'),
            CreateAction::make()->label('Setoran Baru')->icon('heroicon-o-plus')
                ->hidden(fn() => cannotAny(['Unit', 'Coordinator Resources'])),

        ];
    }
}
