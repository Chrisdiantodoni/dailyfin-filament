<?php

namespace App\Filament\Pages;

use App\Filament\Resources\CashierDeposits\CashierDepositResource;
use App\Models\ApprovalCashierDeposit;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Page;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CashierDepositDetail extends  ViewRecord implements HasTable
{

    use InteractsWithTable;

    protected string $view = 'filament.pages.cashier-deposit';

    protected static bool $shouldRegisterNavigation = false;

    protected static string $resource = CashierDepositResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        if (getRole() !== 'Finance Operation' || $this->record->is_seen_ops) {
            return;
        }

        $this->record
            ->forceFill(['is_seen_ops' => true])
            ->saveQuietly();
    }

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
        return 'Detail Setoran Harian Brankas';
    }

    public function getBreadcrumbs(): array
    {
        return [
            static::getResource()::getUrl('index') => 'Daftar Setoran Harian Brankas',
            url()->current() => 'Detail Setoran Brankas',
        ];
    }


    public function confirmationFinanceOpr($record)
    {
        try {
            DB::beginTransaction();
            $record->update([
                'status' => 'approve',
            ]);
            $approval_data = new ApprovalCashierDeposit();
            $approval_data->cashier_deposit_id = $record->id;
            $approval_data->user_id = Auth::user()->id;
            $approval_data->description = "FinOps Menyetujui Laporan Pendapatan Data Kembali ke Kasir";
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
            ]);
            $approval_data = new ApprovalCashierDeposit();
            $approval_data->cashier_deposit_id = $record->id;
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
                        ->label('Nama Lengkap'),
                    TextEntry::make('dealers.dealer_name')
                        ->label('Dealer'),
                    TextEntry::make('start_balance')->money('Rp.', locale: 'ID')
                        ->label('Saldo Awal'),
                    TextEntry::make('today_income')->money('Rp.', locale: 'ID')
                        ->label('Penerimaan Hari Ini'),
                    TextEntry::make('expense')->money('Rp.', locale: 'ID')
                        ->label('Pengeluaran Hari Ini'),
                    TextEntry::make('bank_deposit')->money('Rp.', locale: 'ID')
                        ->label('Setoran ke Bank'),
                    TextEntry::make('end_balance')->money('Rp.', locale: 'ID')
                        ->label('Saldo Akhir'),
                    TextEntry::make('invoice')->money('Rp.', locale: 'ID')
                        ->label('Kasbon Gantung'),
                    TextEntry::make('total_deposit')->money('Rp.', locale: 'ID')
                        ->label('Total Setoran ke Brankas'),
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
                ->columns([
                    'default' => 1,
                    'sm' => 1,
                    'md' => 2,
                    'lg' => 3,
                ])->columnSpanFull(),

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
                        $this->confirmationFinanceOpr($record);
                    })
                    ->hidden(fn($record) => $record->status != 'request'
                        || cannot('Konfirmasi Setoran harian ke Brankas Finance Ops')),


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
                    ->hidden(fn($record) => $record->status != 'request' || cannot('Konfirmasi Setoran harian ke Brankas Finance Ops'))
                    ->action(function ($record, array $data) {
                        // Logic untuk menolak
                        $this->rejectFinanceOpr($record, $data['reason']);
                    }),
                EditAction::make('Edit')
                    ->color('primary')
                    ->hidden(fn($record) => $record->status != 'reject' || can('Konfirmasi Setoran harian ke Brankas Finance Ops'))
                    ->outlined(),
                Action::make('export_pdf')
                    ->label('Print')->hidden(fn($record) => $record->status != 'approve')
                    ->icon('heroicon-o-document-text')
                    ->color('primary')
                    ->url(function () {

                        return route('print.cashier-deposit.pdf', [
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
                ])->columns(1),
            Section::make('Bukti Transfer')
                ->schema([
                    // Kalau langsung mau gambar
                    ImageEntry::make('cashier_images.image')
                        ->label('Gambar')
                        ->getStateUsing(fn($record) => $record->cashier_images->map(
                            fn($img) => upload_url('deposit_box', $img->image)
                        ))
                        ->hiddenLabel()

                    // Atau kalau mau full custom view
                    // ViewEntry::make('proof')
                    //     ->view('filament.tables.images-modal')
                    //     ->viewData([
                    //         'folder' => 'sparepart_deposit',

                    //     ]),
                ])->columns(1),


        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                \App\Models\ApprovalCashierDeposit::query()->where('cashier_deposit_id', $this->record->id)
            )
            ->columns([
                TextColumn::make('user.name')->label('User'),
                TextColumn::make('description')->label('Deskripsi'),
                TextColumn::make('created_at')->label('Waktu')->dateTime('d/m/Y H:i'),
            ])->paginated(false);
    }
}
