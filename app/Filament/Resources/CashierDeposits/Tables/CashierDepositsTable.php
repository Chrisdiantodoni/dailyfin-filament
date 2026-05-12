<?php

namespace App\Filament\Resources\CashierDeposits\Tables;

use App\Filament\Resources\CashierDeposits\CashierDepositResource;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;

class CashierDepositsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date_published')->label('Tanggal')->date('d M Y'),
                TextColumn::make('users.name')->label('Nama Lengkap')->searchable(query: function ($query, $search) {
                    return $query->whereHas('users', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
                }),
                TextColumn::make('dealers.dealer_name')->label('Dealer')->searchable(query: function ($query, $search) {
                    return $query->whereHas('dealers', function ($q) use ($search) {
                        $q->where('dealer_name', 'like', "%{$search}%");
                    });
                }),
                TextColumn::make('start_balance')->label('Saldo Awal')->getStateUsing(fn($record) => formatNumber($record->start_balance))->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('today_income')->label('Pendapatan Hari Ini')->getStateUsing(fn($record) => formatNumber($record->today_income))->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('expense')->label('Pengeluaran Hari Ini')->getStateUsing(fn($record) => formatNumber($record->expense))->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('bank_deposit')->label('Setoran ke Bank')->getStateUsing(fn($record) => formatNumber($record->bank_deposit))->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('end_balance')->label('Saldo Akhir')->getStateUsing(fn($record) => formatNumber($record->end_balance))->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('invoice')->label('Kasbon Gantung')->getStateUsing(fn($record) => formatNumber($record->invoice))->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('total_deposit')->label('Total Setoran ke Brankas')->getStateUsing(fn($record) => formatNumber($record->total_deposit))->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('bank_name')->label('Nama Bank')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('proof')
                    ->label('Bukti Transfer')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->getStateUsing(fn($record) => $record->cashier_images->count() > 0 ? 'Lihat' : 'Tidak ada Gambar.')
                    ->color(fn($record) => $record->cashier_images->count() > 0 ? 'primary' : 'gray')
                    ->action(
                        Action::make('lihatBukti')
                            ->label('Lihat Bukti Transfer')
                            ->icon('heroicon-o-photo')
                            ->modalHeading('Bukti Transfer')
                            ->modalContent(fn($record) => view('filament.tables.images-modal', [
                                'images' => $record->cashier_images,
                                'folder' => 'deposit_box',
                            ]))
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Tutup')
                            ->visible(fn($record) => $record->cashier_images->count() > 0)
                    ),
                TextColumn::make('created_at')
                    ->label('Status Deadline Kasir')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable()
                    ->formatStateUsing(function ($record) {
                        $createdAt = Carbon::parse($record->created_at);
                        $isLate = $createdAt->format('H:i') > '19:00';

                        return $isLate ? 'Late' : 'On-time';
                    })
                    ->icon(function ($record) {
                        $createdAt = Carbon::parse($record->created_at);
                        $isLate = $createdAt->format('H:i') > '19:00';

                        return $isLate ? 'heroicon-o-clock' : 'heroicon-o-check-badge';
                    })
                    ->color(function ($record) {
                        $createdAt = Carbon::parse($record->created_at);
                        $isLate = $createdAt->format('H:i') > '19:00';

                        return $isLate ? 'danger' : 'success';
                    }),

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
                    }),
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

                // Filter status deadline (on-time / late)
                Filter::make('status_deadline')
                    ->label('Status Deadline')
                    ->schema([
                        \Filament\Forms\Components\Select::make('deadline')
                            ->options([
                                'late'    => 'Late',
                                'on_time' => 'On-Time',
                            ])
                            ->placeholder('Semua'),
                    ])
                    ->query(function ($query, array $data) {
                        if (! isset($data['deadline'])) {
                            return $query;
                        }

                        if ($data['deadline'] === 'late') {
                            return $query->whereTime('created_at', '>', '19:00:00');
                        }

                        if ($data['deadline'] === 'on_time') {
                            return $query->whereTime('created_at', '<=', '19:00:00');
                        }

                        return $query;
                    }),
            ])
            ->recordUrl(null)
            ->recordActions([
                Action::make('View')->icon('heroicon-o-eye')
                    ->color('primary')
                    ->url(fn($record) => CashierDepositResource::getUrl('detail', ['record' => $record])),
                EditAction::make()->hidden(fn() => cannot('Coordinator Resources'))->color('danger'),

            ]);
    }
}
