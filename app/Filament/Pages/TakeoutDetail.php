<?php

namespace App\Filament\Pages;

use App\Filament\Resources\TakeoutMoney\TakeoutMoneyResource;
use App\Models\approval_takeout_money;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Page;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Console\Concerns\InteractsWithIO;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TakeoutDetail extends ViewRecord implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.takeout-detail';
    protected static bool $shouldRegisterNavigation = false;

    protected static string $resource = TakeoutMoneyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Kembali')
                ->color('gray')
                ->outlined()
                ->icon('heroicon-o-arrow-left')
                ->url($this->getResource()::getUrl('index')), // ← balik ke ListRecords
        ];
    }

    public function getTitle(): string
    {
        return 'Detail Keluar Uang Brankas';
    }

    public function getBreadcrumbs(): array
    {
        return [
            static::getResource()::getUrl('index') => 'Daftar Keluar Uang Brankas',
            url()->current() => 'Detail',
        ];
    }

    public function confirmationFinanceOpr($record)
    {
        try {
            DB::beginTransaction();
            $record->update([
                'status' => 'approve',
                'approval_type' => '',

            ]);
            $approval_data = new approval_takeout_money();
            $approval_data->cashier_takeouts_id = $record->id;
            $approval_data->user_id = Auth::user()->id;
            $approval_data->description = "Finance Ops Menyetujui Laporan Pengeluaran Uang dan Dilanjutkan Kepada Finance Spv";

            $approval_data->status = "approve";
            $approval_data->save();
            // dd($approval_data);
            DB::commit();
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
        }
    }

    public function rejectFinanceOpr($record, $reason)
    {
        try {
            DB::beginTransaction();
            $record->update([
                'status' => 'reject',
                'is_revised' => true,
                'approval_type' => 'Cashier',
            ]);
            $approval_data = new approval_takeout_money();
            $approval_data->cashier_takeouts_id = $record->id;
            $approval_data->user_id = Auth::user()->id;
            $approval_data->description = $reason;
            $approval_data->status = "reject";
            $approval_data->save();
            DB::commit();
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
        }
    }
    public function infolist(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema->schema([

            Section::make('Informasi Umum')
                ->schema([
                    TextEntry::make('date_published')
                        ->label('Tanggal')->date("d M Y"),
                    TextEntry::make('users.name')
                        ->label('Nama'),
                    TextEntry::make('role')
                        ->label('Bagian')
                        ->getStateUsing(fn($record) => $record->users->roles->first()->name ?? 'Tidak Diketahui'),
                    TextEntry::make('revised_finance_nominal')->money('Rp.', locale: 'ID')
                        ->label('Saldo Revisi'),
                    TextEntry::make('end_balance')->money('Rp.', locale: 'ID')
                        ->label('Jumlah Uang di Brankas'),
                    TextEntry::make('money_put')->money('Rp.', locale: 'ID')
                        ->label('Jumlah Uang Titipan'),
                    TextEntry::make('takeout_nominal')->money('Rp.', locale: 'ID')
                        ->label('Total Uang yang Dikeluarkan'),
                    TextEntry::make('revised_ops_nominal')->money('Rp.', locale: 'ID')
                        ->label('Revisi Jumlah Uang Dikeluarkan'),
                    TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->color(fn($state) => match ($state) {
                            'approve' => 'success',
                            'request' => 'warning',
                            'reject'  => 'danger',
                            default   => 'gray',
                        })->formatStateUsing(fn($state) => match ($state) {
                            'approve' => 'Disetujui',
                            'request' => 'Menunggu',
                            'reject'  => 'Ditolak',
                            default   => ucfirst($state),
                        }),

                ])
                ->columns(3)->columnSpanFull(),

            Actions::make([
                Action::make('confirm')
                    ->label("Konfirmasi")
                    ->color('info')
                    ->modalHeading('Konfirmasi')
                    ->modalDescription('
                    Apakah Anda Yakin?
                    ')
                    ->modalSubmitActionLabel('Ya, Konfirmasi')
                    ->modalCancelActionLabel('Batal')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        // Logic untuk konfirmasi
                        $this->confirmationFinanceOpr($record);
                    })
                    ->hidden(fn($record) => $record->status != 'request' && (getRole() != 'IT' || getRole() != 'Cashier')),


                Action::make('reject')->schema([
                    Textarea::make('reason')
                        ->label('Alasan Penolakan')
                        ->required()
                        ->placeholder('Masukkan alasan...')
                ])
                    ->label("Tolak")
                    ->color('danger')->modalHeading('Tolak Setoran')
                    ->modalDescription('
                    Apakah Anda Yakin?
                    ')
                    ->modalSubmitActionLabel('Ya, Tolak')
                    ->modalCancelActionLabel('Batal')
                    ->hidden(fn($record) => $record->status != 'request' && (getRole() != 'IT' || getRole() != 'Cashier'))
                    ->action(function ($record, array $data) {
                        // Logic untuk menolak
                        $this->rejectFinanceOpr($record, $data['reason']);
                    }),
                EditAction::make('Edit')->label('Revisi')
                    ->color('primary')
                    ->hidden(fn($record) => $record->status != 'reject')
                    ->outlined(),
                Action::make('export_pdf')
                    ->label('Print')->hidden(fn($record) => $record->status != 'approve')
                    ->icon('heroicon-o-document-text')
                    ->color('primary')
                    ->url(function () {

                        return route('print.cashier-takeout.pdf', [
                            'id' => $this->record->id,
                        ]);
                    })
                    ->outlined()
                    ->openUrlInNewTab(),
            ])->extraAttributes([
                'class' => 'flex gap-2 bg-transparent',
            ])->columnSpanFull(),


            Section::make('Keterangan')
                ->schema([
                    TextEntry::make('description')
                        ->columnSpanFull()
                ])->columnSpanFull(),

        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                \App\Models\approval_takeout_money::query()->where('cashier_takeouts_id', $this->record->id)
            )
            ->columns([
                TextColumn::make('user.name')->label('User'),
                TextColumn::make('description')->label('Deskripsi'),
                TextColumn::make('created_at')->label('Waktu')->dateTime('d/m/Y H:i'),
            ])->paginated(false);
    }
}
