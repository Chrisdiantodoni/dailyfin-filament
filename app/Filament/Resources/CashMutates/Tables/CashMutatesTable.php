<?php

namespace App\Filament\Resources\CashMutates\Tables;

use App\Filament\Resources\CashMutates\CashMutateResource;
use App\Models\CashMutate;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;

class CashMutatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date_published')
                    ->date(),
                TextColumn::make('dealers.dealer_name')->label('Dealer')->searchable(query: function ($query, $search) {
                    return $query->whereHas('dealers', function ($q) use ($search) {
                        $q->where('dealer_name', 'like', "%{$search}%");
                    });
                }),
                TextColumn::make('start_balance')->label('Saldo Awal')->searchable()
                    ->getStateUsing(fn($record) => formatNumber($record->start_balance)),
                TextColumn::make('income')->label('Nominal Penerimaan')->searchable()
                    ->getStateUsing(fn($record) => formatNumber($record->income)),
                TextColumn::make('expense')->label('Nominal Pengeluaran')->searchable()
                    ->getStateUsing(fn($record) => formatNumber($record->expense)),
                TextColumn::make('end_balance')->label('Saldo Akhir')->searchable()
                    ->getStateUsing(fn($record) => formatNumber($record->end_balance)),
                TextColumn::make('invoice_nominal')->label('Kasbon Gantung')->searchable()
                    ->getStateUsing(fn($record) => formatNumber($record->invoice_nominal)),
                TextColumn::make('physical_cash')->label('Fisik Kas')->searchable()
                    ->getStateUsing(fn($record) => formatNumber($record->physical_cash)),
                TextColumn::make('proof')
                    ->label('Bukti Fisik Kas')
                    ->getStateUsing(fn($record) => $record->cash_images->count() > 0 ? 'Lihat' : 'Tidak ada Gambar.')
                    ->color(fn($record) => $record->cash_images->count() > 0 ? 'primary' : 'gray')
                    ->action(
                        Action::make('lihatBukti')
                            ->label('Lihat Bukti Transfer')
                            ->icon('heroicon-o-photo')
                            ->modalHeading('Bukti Fisik Kas')
                            ->modalContent(fn($record) => view('filament.tables.images-modal', [
                                'images' => $record->cash_images,
                                'folder' => 'cash_mutates',
                            ]))
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Tutup')
                            ->visible(fn($record) => $record->cash_images->count() > 0)
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
                TextColumn::make('created_at')
                    ->label('Status Deadline')
                    ->formatStateUsing(function ($record) {

                        $createdAt = \Carbon\Carbon::parse($record->created_at);
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
            ])
            ->filters([
                Filter::make('date_range')
                    ->schema([
                        DatePicker::make('start_date')->label('Dari Tanggal'),
                        DatePicker::make('end_date')->label('Sampai Tanggal'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['start_date'], fn($q) => $q->whereDate('date_published', '>=', $data['start_date']))
                            ->when($data['end_date'], fn($q) => $q->whereDate('date_published', '<=', $data['end_date']));
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
                    ->url(fn($record) => CashMutateResource::getUrl('detail', ['record' => $record])),
                EditAction::make()->hidden(fn() => cannot('Coordinator Resources'))->color('danger'),

            ]);
    }
}
