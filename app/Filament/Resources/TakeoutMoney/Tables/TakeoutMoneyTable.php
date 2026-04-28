<?php

namespace App\Filament\Resources\TakeoutMoney\Tables;

use App\Filament\Resources\TakeoutMoney\TakeoutMoneyResource;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;

class TakeoutMoneyTable
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
                TextColumn::make('revised_finance_nominal')->label('Saldo Revisi')
                    ->searchable()
                    ->getStateUsing(fn($record) => formatNumber($record->revised_finance_nominal)),
                TextColumn::make('end_balance')->label('Jumlah Uang di Brankas')
                    ->searchable()
                    ->getStateUsing(fn($record) => formatNumber($record->end_balance)),
                TextColumn::make('money_put')->label('Jumlah Uang Titipan')
                    ->searchable()
                    ->getStateUsing(fn($record) => formatNumber($record->money_put)),
                TextColumn::make('takeout_nominal')->label('Total Uang yang Dikeluarkan')
                    ->searchable()
                    ->getStateUsing(fn($record) => formatNumber($record->takeout_nominal)),
                TextColumn::make('revised_ops_nominal')->label('Revisi Jumlah Uang Dikeluarkan')
                    ->searchable()
                    ->getStateUsing(fn($record) => formatNumber($record->revised_ops_nominal)),
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
            ])
            ->recordUrl(null)
            ->recordActions([
                Action::make('View')->icon('heroicon-o-eye')
                    ->color('primary')
                    ->url(fn($record) => TakeoutMoneyResource::getUrl('detail', ['record' => $record])),
                EditAction::make()->hidden(fn() => cannot('Coordinator Resources'))->color('danger'),

            ]);
    }
}
