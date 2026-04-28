<?php

namespace App\Filament\Pages;

use App\Filament\Resources\ValidationDeposits\ValidationDepositResource;
use App\Models\ApprovalValidation;
use App\Models\ValidationProofImage;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ValidationDepositDetail extends ViewRecord implements HasTable
{
    use InteractsWithTable;
    protected static string $resource = ValidationDepositResource::class;
    protected string $view = 'filament.pages.validation-deposit';
    protected static bool $shouldRegisterNavigation = false;
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
        return 'Detail Validasi Setoran';
    }

    public function getBreadcrumbs(): array
    {
        return [
            static::getResource()::getUrl('index') => 'Daftar Validasi Setoran',
            url()->current() => 'Detail Validasi Setoran',
        ];
    }

    public function confirmationFinanceOpr($record, $proof_imgs)
    {
        try {
            DB::beginTransaction();
            if (!empty($proof_imgs)) {

                foreach ($proof_imgs ?? [] as $filePath) {
                    if (!Storage::disk('public')->exists($filePath)) {
                        continue;
                    }

                    $absolutePath = Storage::disk('public')->path($filePath);

                    $uploadedFile = new UploadedFile(
                        $absolutePath,
                        basename($absolutePath),
                        mime_content_type($absolutePath),
                        null,
                        true
                    );

                    // 1. Generate Nama File menggunakan ULID
                    // Hasilnya: 01H6XCPN8... .webp
                    $filename = Str::ulid()->toBase32() . '.webp';

                    // 2. Proses Konversi (Jika imageService butuh file, tetap teruskan)
                    $compressed = $this->imageService->convertToWebP($uploadedFile);

                    // 3. Simpan via Storage Disk 'public'
                    $targetDir = 'upload/validate_proof';
                    $targetPath = $targetDir . '/' . $filename;

                    // Put file ke storage/app/public/upload/sparepart_deposit/
                    Storage::disk('public')->put($targetPath, (string) $compressed);



                    ValidationProofImage::insert([
                        'image' => $filename,
                        'validation_deposits_id' => $record->id,
                    ]);

                    // 5. Hapus file temporary Filament
                    Storage::disk('public')->delete($filePath);
                }
            }
            $record->update([
                'status' => 'approve',
            ]);
            $approval_data = new ApprovalValidation();
            $approval_data->validation_deposits_id = $record->id;
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
            $approval_data = new ApprovalValidation();
            $approval_data->validation_deposits_id = $record->id;
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
                    Grid::make()
                        ->schema([
                            TextEntry::make('users.name')->label("Nama yang Mengajukan"),
                            TextEntry::make('transaction_type')->label("Jenis Transaksi"),
                            TextEntry::make('dealers.dealer_name')
                                ->label('Dealer'),
                            TextEntry::make('neq_name')->state("-")
                                ->label('NEQ'),


                        ])->columns(1)->columnSpanFull(),
                    TextEntry::make('date_published')
                        ->label('Tanggal')->date("d M Y"),
                    TextEntry::make('nominal_deposit')->money('Rp.', locale: 'ID')
                        ->label('Nominal Setoran'),
                    TextEntry::make('bank_name')->money('Rp.', locale: 'ID')
                        ->label('Nama Bank'),
                    TextEntry::make('deposit_date')
                        ->label('Tanggal Setoran')->date("d M Y"),
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
                Action::make('confirm')->schema([
                    FileUpload::make('proof_imgs')
                        ->label('Upload Bukti Pengecekan')
                        ->disk('public')
                        ->image()          // cuma gambar
                        ->multiple()       // bisa upload banyak
                        ->panelLayout('grid')
                        ->reactive()
                        ->extraInputAttributes([
                            "x-on:livewire-upload-start" => "\$dispatch('file-upload-started')",
                            "x-on:livewire-upload-finish" => "\$dispatch('file-upload-finished')",
                            "x-on:livewire-upload-error" => "\$dispatch('file-upload-error')",

                        ])
                        ->required()
                        ->helperText('Upload Apabila Transfer')
                        ->live()
                        ->validationMessages([
                            'required' => 'Bukti pengecekan wajib diupload!',
                            'image'    => 'File harus berupa gambar.',
                        ])
                        ->mutateDehydratedStateUsing(function ($state) {
                            if (!$state) return [];
                            // Pastikan menyimpan path lengkap relatif terhadap disk 'public'
                            return collect($state)->values()->toArray();
                        })
                ])
                    ->label("Konfirmasi")
                    ->color('info')
                    ->modalHeading('Konfirmasi Setoran')
                    ->modalDescription('
                    Apakah Anda Yakin?
                    ')
                    ->modalSubmitActionLabel('Ya, Konfirmasi')
                    ->modalCancelActionLabel('Batal')
                    ->requiresConfirmation()
                    ->action(function ($record, array $data) {
                        // Logic untuk konfirmasi
                        $this->confirmationFinanceOpr($record, $data['proof_imgs']);
                    })
                    ->hidden(fn($record) => $record->status != 'request' || cannot('Konfirmasi Validasi Setoran')),


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
                    ->hidden(fn($record) => $record->status != 'request' || cannot('Konfirmasi Validasi Setoran'))

                    // ->hidden(fn($record) => $record->status != 'request' && (getRole() != 'IT' || getRole() != 'Finance Spv'))
                    ->action(function ($record, array $data) {
                        // Logic untuk menolak
                        $this->rejectFinanceOpr($record, $data['reason']);
                    }),
                EditAction::make('Edit')
                    ->color('primary')
                    ->hidden(fn($record) => $record->status != 'reject' || can("Konfirmasi Validasi Setoran"))
                    ->outlined(),
                Action::make('export_pdf')
                    ->label('Print')->hidden(fn($record) => $record->status != 'approve')
                    ->icon('heroicon-o-document-text')
                    ->color('primary')
                    ->url(function () {

                        return route('print.validate.pdf', [
                            'id' => $this->record->id,
                        ]);
                    })
                    ->outlined()
                    ->openUrlInNewTab(),
            ])->extraAttributes([
                'class' => 'flex gap-2 bg-transparent',
            ])->columnSpanFull(),

            Section::make('Bukti Setoran')
                ->schema([
                    // Kalau langsung mau gambar
                    ImageEntry::make('validate_imgs.image')
                        ->label('Gambar')
                        ->getStateUsing(fn($record) => $record->validate_imgs->map(
                            fn($img) => asset('storage/upload/validate/' . $img->image)
                        ))
                        ->hiddenLabel()

                    // Atau kalau mau full custom view
                    // ViewEntry::make('proof')
                    //     ->view('filament.tables.images-modal')
                    //     ->viewData([
                    //         'folder' => 'sparepart_deposit',

                    //     ]),
                ])->columns(1),

            Section::make('Bukti Pengecekan')->hidden(fn($record) => $record->status != 'approve')
                ->schema([
                    // Kalau langsung mau gambar
                    ImageEntry::make('proof_imgs.image')
                        ->label('Gambar')
                        ->getStateUsing(fn($record) => $record->proof_imgs->map(
                            fn($img) => asset('storage/upload/validate_proof/' . $img->image)
                        ))
                        ->hiddenLabel()

                    // Atau kalau mau full custom view
                    // ViewEntry::make('proof')
                    //     ->view('filament.tables.images-modal')
                    //     ->viewData([
                    //         'folder' => 'sparepart_deposit',

                    //     ]),
                ])->columns(1),
            Section::make('Keterangan')
                ->schema([
                    TextEntry::make('description')
                        ->columnSpanFull()
                ])->columns(2)->columnSpanFull(),

        ]);
    }
    public function table(Table $table): Table
    {
        return $table
            ->query(
                ApprovalValidation::query()->where('validation_deposits_id', $this->record->id)
            )
            ->columns([
                TextColumn::make('user.name')->label('User'),
                TextColumn::make('description')->label('Deskripsi'),
                TextColumn::make('created_at')->label('Waktu')->dateTime('d/m/Y H:i'),
            ])->paginated(false);
    }
}
