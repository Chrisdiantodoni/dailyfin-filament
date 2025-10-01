<?php

namespace App\Filament\Pages;

use App\Filament\Resources\CounterServiceDeposits\CounterServiceDepositResource;
use App\Models\ApprovalCsCashier;
use App\Models\CsServiceSparepart;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\BasePage;
use Filament\Pages\Page;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use pxlrbt\FilamentActivityLog\Pages\ListActivities;

class CsSparepartDetail extends ViewRecord implements HasTable
{

    use InteractsWithTable;
    protected string $view = 'filament.pages.cs-sparepart2-detail';

    protected static string $resource = CounterServiceDepositResource::class;
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
        return 'Detail Setoran Counter Service';
    }

    public function getBreadcrumbs(): array
    {
        return [
            static::getResource()::getUrl('index') => 'Daftar Setoran',
            url()->current() => 'Detail',
        ];
    }

    public function confirmationCs($record)
    {
        try {
            DB::beginTransaction();
            $record->update([
                'status' => 'approve',
            ]);
            $approval_data = new ApprovalCsCashier();
            $approval_data->cs_service_spareparts_id = $record->id;
            $approval_data->user_id = Auth::user()->id;
            $approval_data->description = "Kasir Telah Menyetujui Laporan Pendapatan Data Kembali ke Counter";
            $approval_data->status = "approve";
            $approval_data->save();
            // dd($approval_data);
            DB::commit();
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
        }
    }

    public function rejectCs($record, $reason)
    {
        try {
            DB::beginTransaction();
            $record->update([
                'status' => 'reject',
            ]);
            $approval_data = new ApprovalCsCashier();
            $approval_data->cs_service_spareparts_id = $record->id;
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
                    TextEntry::make('service_nominal_dtls.cash')->money('Rp.', locale: 'ID')
                        ->label('Setoran Tunai'),
                    TextEntry::make('service_nominal_dtls.transfer')->money('Rp.', locale: 'ID')
                        ->label('Setoran Transfer'),
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


                    TextEntry::make('total_expense')
                        ->label('Nominal Pengeluaran')
                        ->money('Rp.', locale: 'ID'),

                    TextEntry::make('total_income')
                        ->label('Total Disetor')
                        ->money('Rp.', locale: 'ID'),

                ])
                ->columns(3)->columnSpanFull(),

            Actions::make([
                Action::make('confirm')
                    ->label("Konfirmasi")
                    ->color('info')
                    ->modalHeading('Konfirmasi Setoran')
                    ->modalDescription('
                    Apakah Anda Yakin?
                    ')
                    ->modalSubmitActionLabel('Ya, Konfirmasi')
                    ->modalCancelActionLabel('Batal')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        // Logic untuk konfirmasi
                        $this->confirmationCs($record);
                    })
                    ->hidden(fn($record) => $record->status != 'request' || cannot('Konfirmasi Permintaan Setoran Jasa Service')),



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
                    ->hidden(fn($record) => $record->status != 'request' || cannot('Konfirmasi Permintaan Setoran Jasa Service'))
                    ->action(function ($record, array $data) {
                        // Logic untuk menolak
                        $this->rejectCs($record, $data['reason']);
                    }),
                EditAction::make('Edit')
                    ->color('primary')
                    ->hidden(fn($record) => $record->status != 'reject' || can("Konfirmasi Permintaan Setoran Jasa Service"))
                    ->outlined(),
            ])->extraAttributes([
                'class' => 'flex gap-2 bg-transparent',
            ])->columnSpanFull(),


            Section::make('Keterangan')
                ->schema([
                    TextEntry::make('description')
                        ->columnSpanFull()
                ])->columns(1),
            Section::make('Bukti Transfer')
                ->schema([
                    // Kalau langsung mau gambar
                    ImageEntry::make('service_images.image')
                        ->label('Gambar')
                        ->getStateUsing(fn($record) => $record->service_images->map(
                            fn($img) => asset('storage/upload/sparepart_deposit/' . $img->image)
                        ))
                        ->hiddenLabel()

                    // Atau kalau mau full custom view
                    // ViewEntry::make('proof')
                    //     ->view('filament.tables.images-modal')
                    //     ->viewData([
                    //         'folder' => 'sparepart_deposit',

                    //     ]),
                ])->columns(1),

            // Section Riwayat Persetujuan dengan tampilan tabel
            // Section::make('Riwayat Persetujuan')
            //     ->schema([
            // Header tabel
            // Grid::make(4)
            //     ->schema([
            //         TextEntry::make('header_user')
            //             ->label('USER')
            //             ->weight('bold')
            //             ->size('small')
            //             ->extraAttributes(['class' => 'bg-gray-100 '])->columns(1),

            //         TextEntry::make('header_description')
            //             ->label('DESKRIPSI')
            //             ->weight('bold')
            //             ->size('small')
            //             ->extraAttributes(['class' => 'bg-gray-100 '])->columnSpan(2),

            //         TextEntry::make('header_created_at')
            //             ->label('WAKTU')
            //             ->weight('bold')
            //             ->size('small')
            //             ->extraAttributes(['class' => 'bg-gray-100 '])->columns(1),
            //     ])
            //     ->columnSpanFull(),

            // Data tabel
            // RepeatableEntry::make('approval_cs_cashiers')
            //     ->hiddenLabel()
            //     ->schema([
            //         Grid::make(4)
            //             ->schema([
            //                 TextEntry::make('user.name')
            //                     ->label('')->hiddenLabel()
            //                     ->formatStateUsing(fn($state) => $state ?? 'System')
            //                     ->size('small')
            //                     ->extraAttributes(['class' => ' border-b '])->columns(1),

            //                 TextEntry::make('description')
            //                     ->label('')
            //                     ->size('small')->hiddenLabel()
            //                     ->extraAttributes(['class' => 'bg-primary-100 dark:bg-primary-900 p-2 font-bold border-t border-b border-gray-300']),
            //                 TextEntry::make('created_at')
            //                     ->label('')->hiddenLabel()
            //                     ->dateTime('d/m/Y H:i')
            //                     ->size('small')
            //                     ->extraAttributes(['class' => ' border-b'])->columns(1),
            //             ])
            //     ])
            //     ->grid(1)
            //     ->contained(false)
            // ])
            // ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                \App\Models\ApprovalCsCashier::query()->where('cs_service_spareparts_id', $this->record->id)
            )
            ->columns([
                TextColumn::make('user.name')->label('User'),
                TextColumn::make('description')->label('Deskripsi'),
                TextColumn::make('created_at')->label('Waktu')->dateTime('d/m/Y H:i'),
            ])->paginated(false);
    }
}
