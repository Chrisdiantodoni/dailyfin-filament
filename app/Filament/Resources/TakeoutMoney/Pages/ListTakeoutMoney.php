<?php

namespace App\Filament\Resources\TakeoutMoney\Pages;

use App\Exports\ExportTakeout;
use App\Filament\Resources\TakeoutMoney\TakeoutMoneyResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class ListTakeoutMoney extends ListRecords
{
    protected static string $resource = TakeoutMoneyResource::class;
    protected static ?string $title = 'Daftar Keluar Uang Brankas';

    public function getBreadcrumbs(): array
    {
        return [
            'Keluar Uang Brankas',
            'Daftar Keluar Uang Brankas'
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
                        new ExportTakeout(
                            $livewire->tableFilters['date_range']['start_date'] ?? null,
                            $livewire->tableFilters['date_range']['end_date'] ?? null
                        ),
                        'Laporan Keluar Uang Kasir ' .
                            ($livewire->tableFilters['date_range']['start_date'] ?? 'awal') .
                            ' s.d ' .
                            ($livewire->tableFilters['date_range']['end_date'] ?? 'akhir') .
                            '.xlsx'
                    )),

                Action::make('export_pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-text')
                    ->color('danger')
                    ->url(fn($livewire) => route('export.cashier-takeout.pdf', [
                        'start_date' => $livewire->tableFilters['date_range']['start_date'] ?? null,
                        'end_date'   => $livewire->tableFilters['date_range']['end_date'] ?? null,
                    ]))
                    ->openUrlInNewTab(),
            ])->button()
                ->label('Export')
                ->icon('heroicon-o-arrow-down-tray'),
            CreateAction::make()->label("Keluar Uang")->icon('heroicon-o-plus')
                ->hidden(fn() => cannotAny(['Keluarkan Uang Kasir', 'Coordinator Resources'])),

            // ->hidden(fn() => cannot('Keluarkan Uang Kasir')),
        ];
    }
}
