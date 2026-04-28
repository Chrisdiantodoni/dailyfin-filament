<?php

namespace App\Filament\Resources\CounterServiceUnits\Tables;

use App\Filament\Resources\CounterServiceUnits\CounterServiceUnitResource;
use App\Filament\Resources\CounterServiceUnits\Schemas\CounterServiceUnitForm;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;

class CounterServiceUnitsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date_published')->label('Tanggal')->date('d M Y'),
                TextColumn::make('users.name')->label('Nama Pengguna')->searchable(query: function ($query, $search) {
                    return $query->whereHas('users', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
                }),
                TextColumn::make('dealers.dealer_name')->label('Dealer')->searchable(query: function ($query, $search) {
                    return $query->whereHas('dealers', function ($q) use ($search) {
                        $q->where('dealer_name', 'like', "%{$search}%");
                    });
                }),
                TextColumn::make('roles')
                    ->label('Bagian')
                    ->getStateUsing(fn($record) => $record->users->roles->pluck('name')->join(', '))
                    ->searchable(query: function ($query, $search) {
                        return $query->whereHas('users.roles', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                    })
                    ->getStateUsing(fn($record) => $record->users->roles->first()->name ?? 'Tidak Diketahui'),
                TextColumn::make('cash')->label('Setoran Tunai')->searchable(query: function ($query, $search) {
                    return $query->whereHas('unit_nominal_dtls', function ($query) use ($search) {
                        return $query->where('cash', 'like', "%{$search}%");
                    });
                })
                    ->getStateUsing(fn($record) => formatNumber($record->unit_nominal_dtls->cash)),
                TextColumn::make('credit')->label('Setoran Transfer')->searchable(query: function ($query, $search) {
                    return $query->whereHas('unit_nominal_dtls', function ($query) use ($search) {
                        return $query->where('transfer', 'like', "%{$search}%");
                    });
                })
                    ->getStateUsing(fn($record) => formatNumber($record->unit_nominal_dtls->transfer)),
                TextColumn::make('total_expense')->label('Nominal Pengeluaran')->searchable()
                    ->getStateUsing(fn($record) => formatNumber($record->total_expense)),
                TextColumn::make('total_income')->label('Total Disetor')->searchable()
                    ->getStateUsing(fn($record) => formatNumber($record->total_income)),
                TextColumn::make('proof')
                    ->label('Bukti Transfer')
                    ->getStateUsing(fn($record) => $record->unit_images->count() > 0 ? 'Lihat' : 'Tidak ada Gambar.')
                    ->color(fn($record) => $record->unit_images->count() > 0 ? 'primary' : 'gray')
                    ->action(
                        Action::make('lihatBukti')
                            ->label('Lihat Bukti Transfer')
                            ->icon('heroicon-o-photo')
                            ->modalHeading('Bukti Transfer')
                            ->modalContent(fn($record) => view('filament.tables.images-modal', [
                                'images' => $record->unit_images,
                                'folder' => 'unit_deposit',
                            ]))
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Tutup')
                            ->visible(fn($record) => $record->unit_images->count() > 0)
                    ),
                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'request' => 'Menunggu',
                        'approve' => 'Disetujui',
                        'reject'  => 'Ditolak',
                        default   => ucfirst($state),
                    })
                    ->icon(fn(string $state): string => match ($state) {
                        'request' => 'heroicon-o-arrow-path',   // mirip fa-rotate-right
                        'approve' => 'heroicon-o-check',
                        'reject'  => 'heroicon-o-x-mark',
                        default   => 'heroicon-o-question-mark-circle',
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'request' => 'primary',
                        'approve' => 'success',
                        'reject'  => 'danger',
                        default   => 'gray',
                    }),
            ])
            ->recordUrl(null)
            ->filters([
                Filter::make('date_range')
                    ->schema([
                        DatePicker::make('start_date')
                            ->label('Dari Tanggal')
                            // Default: 1 bulan yang lalu
                            ->default(now()->subMonth()->format('Y-m-d')),

                        DatePicker::make('end_date')
                            ->label('Sampai Tanggal')
                            // Default: Hari ini
                            ->default(now()->format('Y-m-d')),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['start_date'], fn($q) => $q->whereDate('date_published', '>=', $data['start_date']))
                            ->when($data['end_date'], fn($q) => $q->whereDate('date_published', '<=', $data['end_date']));
                    })->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['start_date'] ?? null) {
                            $indicators['start_date'] = 'Dari: ' . \Carbon\Carbon::parse($data['start_date'])->toFormattedDateString();
                        }
                        if ($data['end_date'] ?? null) {
                            $indicators['end_date'] = 'Sampai: ' . \Carbon\Carbon::parse($data['end_date'])->toFormattedDateString();
                        }
                        return $indicators;
                    }),,
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
                    ->url(fn($record) => CounterServiceUnitResource::getUrl('detail', ['record' => $record])),
                EditAction::make()->hidden(fn() => cannot('Coordinator Resources'))->color('danger'),

            ])
            ->toolbarActions([
                // BulkActionGroup::make([
                //     DeleteBulkAction::make(),
                // ]),
            ]);
    }
}
