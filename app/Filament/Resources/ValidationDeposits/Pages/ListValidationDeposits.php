<?php

namespace App\Filament\Resources\ValidationDeposits\Pages;

use App\Exports\ExportValidate;
use App\Filament\Resources\ValidationDeposits\ValidationDepositResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class ListValidationDeposits extends ListRecords
{
    protected static string $resource = ValidationDepositResource::class;
    protected static ?string $title = 'Daftar Validasi Setoran';

    public function getBreadcrumbs(): array
    {
        return [
            'Validasi Setoran',
            'Daftar Validasi Setoran'
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
                        new ExportValidate(
                            $livewire->tableFilters['date_range']['start_date'] ?? null,
                            $livewire->tableFilters['date_range']['end_date'] ?? null
                        ),
                        'Report Validasi Setoran ' .
                            ($livewire->tableFilters['date_range']['start_date'] ?? 'awal') .
                            ' s.d ' .
                            ($livewire->tableFilters['date_range']['end_date'] ?? 'akhir') .
                            '.xlsx'
                    )),

                Action::make('export_pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-text')
                    ->color('danger')
                    ->url(fn($livewire) => route('export.validate.pdf', [
                        'start_date' => $livewire->tableFilters['date_range']['start_date'] ?? null,
                        'end_date'   => $livewire->tableFilters['date_range']['end_date'] ?? null,
                    ]))
                    ->openUrlInNewTab(),
            ])->button()
                ->label('Export')
                ->icon('heroicon-o-arrow-down-tray'),
            CreateAction::make()->label('Setoran Baru')->icon('heroicon-o-plus')
                ->hidden(fn() => cannotAny(['Validasi Setoran', 'Coordinator Resources'])),

            // ->hidden(fn() => cannot('Validasi Setoran')),

        ];
    }
}
