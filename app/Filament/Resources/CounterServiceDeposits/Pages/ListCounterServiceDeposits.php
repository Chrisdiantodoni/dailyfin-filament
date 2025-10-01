<?php

namespace App\Filament\Resources\CounterServiceDeposits\Pages;

use App\Exports\ExportGeneral;
use App\Filament\Resources\CounterServiceDeposits\CounterServiceDepositResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class ListCounterServiceDeposits extends ListRecords
{
    protected static string $resource = CounterServiceDepositResource::class;
    protected static ?string $title = 'Daftar Setoran';


    // protected function authorizeAccess(): void
    // {

    //     abort_unless(can('Konfirmasi Permintaan Setoran Jasa Service') || can("Setoran Ke Kasir"), 403);
    // }

    public function getBreadcrumbs(): array
    {
        return [
            'Sparepart dan Jasa',
            'Daftar Setoran Sparepart dan Jasa'
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
                        new ExportGeneral(
                            $livewire->tableFilters['date_range']['start_date'] ?? null,
                            $livewire->tableFilters['date_range']['end_date'] ?? null
                        ),
                        'Setoran Counter Jasa Service ke Kasir dari ' .
                            ($livewire->tableFilters['date_range']['start_date'] ?? 'awal') .
                            ' s.d ' .
                            ($livewire->tableFilters['date_range']['end_date'] ?? 'akhir') .
                            '.xlsx'
                    )),

                Action::make('export_pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-text')
                    ->color('danger')
                    ->url(fn($livewire) => route('export.general.pdf', [
                        'start_date' => $livewire->tableFilters['date_range']['start_date'] ?? null,
                        'end_date'   => $livewire->tableFilters['date_range']['end_date'] ?? null,
                    ]))
                    ->openUrlInNewTab(),
            ])->button()
                ->label('Export')
                ->icon('heroicon-o-arrow-down-tray'),
            CreateAction::make()->label('Setoran Baru')->icon('heroicon-o-plus')
                ->hidden(fn() => cannotAny(['Jasa Service', 'Coordinator Resources'])),
        ];
    }
}
