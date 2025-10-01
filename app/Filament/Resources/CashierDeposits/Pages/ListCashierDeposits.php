<?php

namespace App\Filament\Resources\CashierDeposits\Pages;

use App\Exports\ExportDeposit;
use App\Filament\Resources\CashierDeposits\CashierDepositResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class ListCashierDeposits extends ListRecords
{
    protected static string $resource = CashierDepositResource::class;
    protected static ?string $title = 'Daftar Setoran Brankas';

    public function getBreadcrumbs(): array
    {
        return [
            'Setoran Harian Brankas',
            'Daftar Setoran Harian Brankas'
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
                        new ExportDeposit(
                            $livewire->tableFilters['date_range']['start_date'] ?? null,
                            $livewire->tableFilters['date_range']['end_date'] ?? null
                        ),
                        'Report Setoran Kasir ke Brankas ' .
                            ($livewire->tableFilters['date_range']['start_date'] ?? 'awal') .
                            ' s.d ' .
                            ($livewire->tableFilters['date_range']['end_date'] ?? 'akhir') .
                            '.xlsx'
                    )),

                Action::make('export_pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-text')
                    ->color('danger')
                    ->url(fn($livewire) => route('export.cashier-deposit.pdf', [
                        'start_date' => $livewire->tableFilters['date_range']['start_date'] ?? null,
                        'end_date'   => $livewire->tableFilters['date_range']['end_date'] ?? null,
                    ]))
                    ->openUrlInNewTab(),
            ])->button()
                ->label('Export')
                ->icon('heroicon-o-arrow-down-tray'),
            CreateAction::make()->label('Setoran Brankas')->icon('heroicon-o-plus')
                ->hidden(fn() => cannotAny(['Setoran Harian ke Brankas', 'Coordinator Resources'])),
        ];
    }
}
