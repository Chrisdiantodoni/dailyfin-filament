<?php

namespace App\Filament\Resources\ValidationDeposits\Tables;

use App\Filament\Resources\ValidationDeposits\ValidationDepositResource;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;

class ValidationDepositsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('date_published')
                    ->date()->label('Tanggal'),
                TextColumn::make('dealers.dealer_name')->label('Dealer')->searchable(query: function ($query, $search) {
                    return $query->whereHas('dealers', function ($q) use ($search) {
                        $q->where('dealer_name', 'like', "%{$search}%");
                    });
                }),
                TextColumn::make('neq_name')->label('NEQ')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('customer_name')->label('Nama Penyetor')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('nominal_deposit')->label('Nominal Penerimaan')->searchable()
                    ->getStateUsing(fn($record) => formatNumber($record->nominal_deposit)),
                TextColumn::make('deposit_date')->label('Tanggal Setoran')->searchable()->date()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('transaction_type')->label('Jenis Transaksi')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('proof')
                    ->label('Bukti Setoran')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->getStateUsing(fn($record) => $record->validate_imgs->count() > 0 ? 'Lihat' : 'Tidak ada Gambar.')
                    ->color(fn($record) => $record->validate_imgs->count() > 0 ? 'primary' : 'gray')
                    ->action(
                        Action::make('lihatBukti')
                            ->label('Lihat Bukti Transfer')
                            ->icon('heroicon-o-photo')
                            ->modalHeading('Bukti Setoran')
                            ->modalContent(fn($record) => view('filament.tables.images-modal', [
                                'images' => $record->validate_imgs,
                                'folder' => 'validate',
                            ]))
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Tutup')
                            ->visible(fn($record) => $record->validate_imgs->count() > 0)
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
                TextColumn::make('deadline_submit')
                    ->label('Status Deadline Submit')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->getStateUsing(function ($record) {
                        // return "$record->created_at, $record->date_published";
                        return isLateValidateDeposit($record->created_at, $record->date_published)
                            ? 'Late'
                            : 'On-time';
                    })

                    ->formatStateUsing(function ($record) {
                        return isLateValidateDeposit($record->created_at, $record->date_published)
                            ? 'Late'
                            : 'On-time';
                    })
                    ->icon(function ($record) {
                        return isLateValidateDeposit($record->created_at, $record->date_published)
                            ? 'heroicon-o-clock'
                            : 'heroicon-o-check-badge';
                    })
                    ->color(function ($record) {
                        return isLateValidateDeposit($record->created_at, $record->date_published)
                            ? 'danger'
                            : 'success';
                    }),
                TextColumn::make('deadline_approval')
                    ->label('Status Deadline Approval')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->getStateUsing(fn($record) => $record->status_deadline)
                    ->formatStateUsing(function ($record) {
                        return $record->status_deadline == "Late"
                            ? 'Late'
                            : 'On-time';
                    })
                    ->icon(function ($record) {
                        return $record->status_deadline == "Late"
                            ? 'heroicon-o-clock'
                            : 'heroicon-o-check-badge';
                    })
                    ->color(function ($record) {
                        return $record->status_deadline == "Late"
                            ?  'danger'
                            : 'success';
                    })

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
                Filter::make('status_deadline')
                    ->label('Status Deadline Submit')
                    ->schema([
                        Select::make('deadline')
                            ->options([
                                'late'    => 'Late',
                                'on_time' => 'On-Time',
                            ])
                            ->placeholder('Semua'),
                    ])
                    ->query(function ($query, array $data) {
                        if (empty($data['deadline'])) {
                            return $query;
                        }

                        return $query->where(function ($q) use ($data) {
                            if ($data['deadline'] === 'late') {
                                // Deadline = date_published + 1 hari jam 12:00
                                $q->whereRaw("
                    created_at > TIMESTAMPADD(
                        HOUR, 12,
                        DATE_ADD(date_published, INTERVAL 1 DAY)
                    )
                ");
                            }

                            if ($data['deadline'] === 'on_time') {
                                $q->whereRaw("
                    created_at <= TIMESTAMPADD(
                        HOUR, 12,
                        DATE_ADD(date_published, INTERVAL 1 DAY)
                    )
                ");
                            }
                        });
                    }),

                Filter::make('status_deadline_approval')
                    ->label('Status Deadline Approval')
                    ->schema([
                        \Filament\Forms\Components\Select::make('deadline_approval')
                            ->options([
                                'late'    => 'Late',
                                'on_time' => 'On-time',
                            ])
                            ->placeholder('Semua'),
                    ])
                    ->query(function ($query, array $data) {
                        if (! isset($data['deadline_approval'])) {
                            return $query;
                        }

                        if ($data['deadline_approval'] === 'late') {
                            return $query->whereTime('status_deadline', '=', 'Late');
                        }

                        if ($data['deadline_approval'] === 'on_time') {
                            return $query->whereTime('status_deadline', '<=', 'On-time');
                        }

                        return $query;
                    }),

            ])
            ->recordUrl(null)

            ->recordActions([
                Action::make('View')->icon('heroicon-o-eye')
                    ->color('primary')
                    ->url(fn($record) => ValidationDepositResource::getUrl('detail', ['record' => $record])),
                EditAction::make()->hidden(fn() => cannot('Coordinator Resources'))->color('danger'),

            ]);
    }
}
