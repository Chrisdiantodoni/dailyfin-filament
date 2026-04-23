<?php

namespace App\Filament\Pages;

use App\Exports\ExportCoordinator;
use App\Models\CashierDeposit;
use App\Models\CashierDepositMutate;
use App\Models\Coordinator as ModelsCoordinator;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Excel;

class Coordinator extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ClipboardDocumentCheck;
    protected static ?int $navigationSort = 5;

    public function getBreadcrumbs(): array
    {
        return [
            'Daftar Laporan Koordinator',
        ];
    }
    public static function canAccess(): bool
    {
        /** @var \App\Models\User&\Spatie\Permission\Traits\HasRoles $user */
        $user = Auth::user();
        return $user->hasPermissionTo("Coordinator");
    }
    public function getTitle(): string
    {
        return 'Daftar Laporan Koordinator';
    }
    protected string $view = 'filament.pages.coordinator';

    public function store($record)
    {

        $coordinator = ModelsCoordinator::updateOrCreate(
            [
                'dealer_code' => $record->dealer_code,
                'date_published' => $record->date_published,
            ],
            [
                'start_balance_cashier' => $record->start_balance_cashier,
                'end_balance_cashier' => $record->end_balance_cashier,
                'invoice_cashier' => $record->invoice_cashier,
                'start_balance_mutates' => $record->start_balance_mutates,
                'end_balance_mutates' => $record->end_balance_mutates,
                'invoice_mutates' => $record->invoice_mutates,
                'status' => 'request',
            ]
        );
        $url = CoordinatorDetail::getUrl(['id' => $coordinator->id]);
        return redirect()->to($url);
    }

    public function getHeaderActions(): array
    {
        return [];
    }


    public function table(Table $table): Table
    {
        return $table
            ->query(fn() => CashierDepositMutate::getReportQuery())
            ->paginated(true)
            ->headerActions([
                ActionGroup::make([
                    Action::make('export_excel')
                        ->label('Excel')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('success')
                        ->action(function () {
                            $filters = $this->getTableFilterState('date_range');

                            $start = $filters['start_date'] ?? null;
                            $end   = $filters['end_date'] ?? null;

                            return \Maatwebsite\Excel\Facades\Excel::download(
                                new ExportCoordinator($start, $end),
                                'Report Setoran Kasir ke Brankas ' . ($start ?? 'awal') . ' s.d ' . ($end ?? 'akhir') . '.xlsx'
                            );
                        }),

                    Action::make('export_pdf')
                        ->label('PDF')
                        ->icon('heroicon-o-document-text')
                        ->color('danger')
                        ->url(function () {
                            $filters = $this->getTableFilterState('date_range');

                            return route('export.coordinator.pdf', [
                                'start_date' => $filters['start_date'] ?? null,
                                'end_date'   => $filters['end_date'] ?? null,
                            ]);
                        })
                        ->openUrlInNewTab(),
                ])->button()
                    ->label('Export')
                    ->icon('heroicon-o-arrow-down-tray'),
            ])
            ->filters([
                Filter::make('date_range')
                    ->schema([
                        DatePicker::make('start_date')->label('Dari Tanggal'),
                        DatePicker::make('end_date')->label('Sampai Tanggal'),
                    ])->query(
                        function ($query, array $data) {
                            // FILTERING DILAKUKAN DI SINI
                            return $query
                                ->when($data['start_date'], function ($q, $date) {
                                    return $q->whereDate('cashier_deposits.date_published', '>=', $date);
                                })
                                ->when($data['end_date'], function ($q, $date) {
                                    return $q->whereDate('cashier_deposits.date_published', '<=', $date);
                                });
                        }
                    ),
                Filter::make('status')
                    ->label('Status Workflow')
                    ->schema([
                        \Filament\Forms\Components\Select::make('status')
                            ->options([
                                'request' => 'Menunggu',
                                'approve' => 'Disetujui',
                                'reject'  => 'Ditolak',
                            ])
                            ->placeholder('Semua'),
                    ])
                    ->query(function ($query, array $data) {
                        if (! isset($data['status'])) {
                            return $query;
                        }
                        return $query->where('status', $data['status']);
                    }),
            ])
            ->recordActions([
                Action::make('View')->icon('heroicon-o-eye')
                    ->color('primary')
                    ->action(fn($record) => $this->store($record))
                // ->url(fn($record): string => CoordinatorDetail::getUrl([
                //     'dealer_code' => $record->dealer_code,
                //     'date_published' => $record->date_published,
                //     'start_balance_cashier' => $record->start_bala
                // ])),
            ])
            ->columns([
                TextColumn::make('date_published')
                    ->label('Tanggal')
                    ->date('d M Y'),

                TextColumn::make('dealer_name')
                    ->label('Nama Dealer')->searchable(),

                // Blok Saldo Harian Kasir
                TextColumn::make('start_balance_cashier')
                    ->label('Saldo Awal')->label("Saldo Awal Kasir")->searchable()
                    ->money('idr', true),
                TextColumn::make('end_balance_cashier')->label("Saldo Akhir Kasir")->searchable()
                    ->label('Saldo Akhir')
                    ->money('idr', true),
                TextColumn::make('invoice_cashier')->label("Kas Gantung Kasir")->searchable()
                    ->label('Kas Gantung')
                    ->money('idr', true),


                // Blok Saldo GL (mutasi)
                TextColumn::make('start_balance_mutates')->label("Saldo Awal GL")->searchable()
                    ->label('Saldo Awal')
                    ->money('idr', true),
                TextColumn::make('end_balance_mutates')->label("Saldo Akhir GL")->searchable()
                    ->label('Saldo Akhir')
                    ->money('idr', true),
                TextColumn::make('invoice_mutates')->label("Kas Gantung Kasir")->searchable()
                    ->label('Kas Gantung')
                    ->money('idr', true),


                // Blok Selisih
                TextColumn::make('selisih_awal')
                    ->label("Selisih Saldo Awal")
                    ->color(fn($state) => $state < 0 ? 'danger' : null) // merah jika minus
                    ->getStateUsing(fn($record) => $record->start_balance_cashier - $record->start_balance_mutates)
                    ->money('idr', true),
                TextColumn::make('selisih_akhir')
                    ->label('Selisih Saldo Akhir')
                    ->color(fn($state) => $state < 0 ? 'danger' : null) // merah jika minus
                    ->getStateUsing(fn($record) => $record->end_balance_cashier - $record->end_balance_mutates)
                    ->money('idr', true),
                TextColumn::make('selisih_invoice')
                    ->label('Selisih Kas Gantung')
                    ->color(fn($state) => $state < 0 ? 'danger' : null) // merah jika minus

                    ->getStateUsing(fn($record) => $record->invoice_cashier - $record->invoice_mutates)
                    ->money('idr', true),
                TextColumn::make('status')->getStateUsing(fn($record) => $record->state == "" ? "request" : $record->state)
                    ->label('Status')
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'request' => 'Menunggu',
                        'approve' => 'Disetujui',
                        'reject'  => 'Ditolak',
                        '' => 'Menunggu',
                        default   => ucfirst($state),
                    })
                    ->icon(fn(string $state): string => match ($state) {
                        'request' => 'heroicon-o-arrow-path',   // mirip fa-rotate-right
                        'approve' => 'heroicon-o-check',
                        'reject'  => 'heroicon-o-x-mark',
                        '' => 'heroicon-o-arrow-path',   // mirip fa-rotate-right
                        default   => 'heroicon-o-question-mark-circle',
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'request' => 'primary',
                        'approve' => 'success',
                        'reject'  => 'danger',
                        '' => 'primary',
                        default   => 'gray',
                    }),
            ]);
    }



    public function getTableRecordKey(mixed $record): string
    {
        $key = $record->getKey();

        if (! is_null($key) && $key !== '') {
            return (string) $key;
        }

        // fallback kalau id null
        if (! empty($record->dealer_code) && ! empty($record->date_published)) {
            return (string) ($record->dealer_code . '_' . $record->date_published);
        }

        return uniqid('row_', true);
    }
}
