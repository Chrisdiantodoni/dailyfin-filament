<?php

namespace App\Filament\Resources\CashMutates\Pages;

use App\Exports\ExportMutate;
use App\Filament\Resources\CashMutates\CashMutateResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListCashMutates extends ListRecords
{
    protected static string $resource = CashMutateResource::class;

    protected static ?string $title = 'Daftar Mutasi Kas';

    public function getBreadcrumbs(): array
    {
        return [
            'Mutasi Kas',
            'Daftar Mutasi Kas'
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
                        new ExportMutate(
                            $livewire->tableFilters['date_range']['start_date'] ?? null,
                            $livewire->tableFilters['date_range']['end_date'] ?? null
                        ),
                        'Report Mutasi Kas ' .
                            ($livewire->tableFilters['date_range']['start_date'] ?? 'awal') .
                            ' s.d ' .
                            ($livewire->tableFilters['date_range']['end_date'] ?? 'akhir') .
                            '.xlsx'
                    )),

                Action::make('export_pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-text')
                    ->color('danger')
                    ->url(fn($livewire) => route('export.mutate.pdf', [
                        'start_date' => $livewire->tableFilters['date_range']['start_date'] ?? null,
                        'end_date'   => $livewire->tableFilters['date_range']['end_date'] ?? null,
                    ]))
                    ->openUrlInNewTab(),
            ])->button()
                ->label('Export')
                ->icon('heroicon-o-arrow-down-tray'),
            CreateAction::make()->label('Mutasi Baru')->icon('heroicon-o-plus')
                ->hidden(fn() => cannotAny(['Mutasi Kas', 'Coordinator Resources'])),

            // ->hidden(fn() => cannot('Mutasi Kas')),
        ];
    }
}
