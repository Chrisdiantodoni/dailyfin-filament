<?php

namespace App\Filament\Pages;

use App\Filament\Resources\CashMutates\CashMutateResource;
use App\Models\ApprovalMutateCash;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Page;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Support\RawJs;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;

class CashMutateDetail extends  ViewRecord implements HasTable
{
    use InteractsWithTable;
    protected static string $resource = CashMutateResource::class;
    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.cash-mutate-detail';

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
        return 'Detail Mutasi Kas';
    }

    public function getBreadcrumbs(): array
    {
        return [
            static::getResource()::getUrl('index') => 'Daftar Mutasi Kas',
            url()->current() => 'Detail Mutasi Kas',
        ];
    }

    public function confirmationFinanceOpr($record)
    {
        try {
            DB::beginTransaction();
            $latest_approval = $record->deadline_time;
            $isLate = Carbon::now()->isAfter($latest_approval);

            if ($isLate) {
                $record->update([
                    'status' => 'approve',
                    'status_deadline' => 'Late'
                ]);
            } else {
                $record->update([
                    'status' => 'approve',
                    'status_deadline' => 'On-time'
                ]);
            }
            $approval_data = new ApprovalMutateCash();
            $approval_data->user_id = Auth::user()->id;
            $approval_data->description = "Finance Spv Menyetujui Laporan Mutasi Kas";
            $approval_data->status = "approve";
            $approval_data->cash_mutates_id = $record->id;
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
            $approval_data = new ApprovalMutateCash();
            $approval_data->user_id = Auth::user()->id;
            $approval_data->description = $reason;
            $approval_data->status = "reject";
            $approval_data->cash_mutates_id = $record->id;
            $approval_data->save();

            DB::commit();
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
        }
    }

    public function addNeqsDeposit($record, $data)
    {
        try {
            DB::beginTransaction();
            $record->update($data);
            $approval_data = new ApprovalMutateCash();
            $approval_data->user_id = Auth::user()->id;
            $approval_data->description = "Terdapat Pengisian Setoran NEQ";
            $approval_data->status = "approve";
            $approval_data->cash_mutates_id = $record->id;
            $approval_data->save();

            DB::commit();
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
        }
    }
    private function makeDenomGrid(string $field, int $value, string $label): \Filament\Schemas\Components\Grid
    {
        return \Filament\Schemas\Components\Grid::make(12)->schema([

            // Jumlah Lembar
            \Filament\Infolists\Components\TextEntry::make($field)
                ->label("Jumlah")->hiddenLabel()
                ->default(0) // 👈 kasih default kalau null
                ->formatStateUsing(fn($state) => ($state ?? 0) . ' lbr')
                ->extraAttributes(['class' => 'flex items-center justify-center font-semibold'])
                ->columnSpan(3),

            // x
            \Filament\Infolists\Components\TextEntry::make("multiply_$field")
                ->state('x')
                ->hiddenLabel()
                ->extraAttributes(['class' => 'flex items-center justify-center'])
                ->columnSpan(1),

            // Nominal
            \Filament\Infolists\Components\TextEntry::make("cash_denomination_$field")
                ->state($label)
                ->hiddenLabel()
                ->extraAttributes(['class' => 'flex items-center justify-center font-semibold'])
                ->columnSpan(3),

            // =
            \Filament\Infolists\Components\TextEntry::make("equals_$field")
                ->state('=')
                ->hiddenLabel()
                ->extraAttributes(['class' => 'flex items-center justify-center'])
                ->columnSpan(1),

            // Subtotal
            \Filament\Infolists\Components\TextEntry::make("sub_total_$field")
                ->label('Subtotal')->hiddenLabel()
                ->state(function ($get) use ($field, $value) {
                    $jumlah = (int) ($get($field) ?? 0);
                    return $jumlah * $value;
                })
                ->formatStateUsing(fn($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                ->extraAttributes(['class' => 'flex items-center text-primary-600'])
                ->columnSpan(4),
        ]);
    }

    public function infolist(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema->schema([

            Section::make('Informasi Umum')
                ->schema([
                    TextEntry::make('date_published')
                        ->label('Tanggal')->date("d M Y"),
                    TextEntry::make('start_balance')->money('Rp.', locale: 'ID')
                        ->label('Saldo Awal'),
                    TextEntry::make('income')->money('Rp.', locale: 'ID')
                        ->label('Penerimaan'),
                    TextEntry::make('expense')->money('Rp.', locale: 'ID')
                        ->label('Pengeluaran'),
                    TextEntry::make('end_balance')->money('Rp.', locale: 'ID')
                        ->label('Saldo Akhir'),
                    TextEntry::make('invoice_nominal')->money('Rp.', locale: 'ID')
                        ->label('Kasbon Gantung'),
                    TextEntry::make('physical_cash')->money('Rp.', locale: 'ID')
                        ->label('Fisik Kas'),
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
                    ->hidden(fn($record) => $record->status != 'request' || cannot("Konfirmasi Mutasi Uang")),


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
                    ->hidden(fn($record) => $record->status != 'request' || cannot("Konfirmasi Mutasi Uang"))
                    ->action(function ($record, array $data) {
                        // Logic untuk menolak
                        $this->rejectFinanceOpr($record, $data['reason']);
                    }),
                EditAction::make('Edit')
                    ->color('primary')
                    ->hidden(fn($record) => $record->status != 'reject' || can("Konfirmasi Mutasi Uang"))
                    ->outlined(),
                Action::make('export_pdf')
                    ->label('Print')->hidden(fn($record) => $record->status != 'approve')
                    ->icon('heroicon-o-document-text')
                    ->color('primary')
                    ->url(function () {

                        return route('print.cash-mutation.pdf', [
                            'id' => $this->record->id,
                        ]);
                    })
                    ->outlined()
                    ->openUrlInNewTab(),
                Action::make('neqs_incomes')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('neq_incomes')
                                ->mask(RawJs::make('$money($input, \',\', \'.\')'))
                                ->label('Pendapatan NEQ')
                                ->live(onBlur: true)
                                ->extraAttributes([
                                    'id' => 'income_neq'
                                ])
                                ->stripCharacters("."),
                            TextInput::make('neq_expenses')
                                ->mask(RawJs::make('$money($input, \',\', \'.\')'))
                                ->label('Pengeluaran NEQ')
                                ->live(onBlur: true)
                                ->extraAttributes([
                                    'id' => 'neq_expenses'
                                ])
                                ->stripCharacters("."),
                        ]),
                    ])
                    ->action(function ($record, array $data) {
                        $this->addNeqsDeposit($record, $data);
                    })
                    ->label('Setoran NEQ')->hidden(fn($record) => $record->status != 'approve')
                    ->icon('heroicon-o-plus')
                    ->color('primary'),

            ])->extraAttributes([
                'class' => 'flex gap-2 bg-transparent',
            ])->columnSpanFull(),
            Section::make('Rincian Fisik Kas')
                ->schema([
                    $this->makeDenomGrid('denom_100k', 100000, 'Rp 100.000,00'),
                    $this->makeDenomGrid('denom_2k',   2000,   'Rp 2.000,00'),
                    $this->makeDenomGrid('denom_75k',  75000,  'Rp 75.000,00'),
                    $this->makeDenomGrid('denom_1k',   1000,   'Rp 1.000,00'),
                    $this->makeDenomGrid('denom_50k',  50000,  'Rp 50.000,00'),
                    $this->makeDenomGrid('denom_500',  500,    'Rp 500,00'),
                    $this->makeDenomGrid('denom_20k',  20000,  'Rp 20.000,00'),
                    $this->makeDenomGrid('denom_200',  200,    'Rp 200,00'),
                    $this->makeDenomGrid('denom_10k',  10000,  'Rp 10.000,00'),
                    $this->makeDenomGrid('denom_100',  100,    'Rp 100,00'),
                    $this->makeDenomGrid('denom_5k',   5000,   'Rp 5.000,00'),
                ])->columns(2)->columnSpanFull(),


            TextEntry::make('total_physical_cash')
                ->label('Total Fisik Kas')
                ->money('Rp.', locale: 'ID')
                ->extraAttributes(['class' => 'text-2xl font-bold text-primary-700'])
                ->columnSpanFull(),
            Section::make('Keterangan')
                ->schema([
                    // TextEntry::make('description2')->hiddenLabel(),
                    new HtmlString(
                        '<div class="fi-prose">' .
                            ($this->record && $this->record->description2
                                ? RichContentRenderer::make($this->record->description2)->toHtml()
                                : ''
                            ) .
                            '</div>'
                    ),

                    // TextEntry::make('description2')
                    //     ->hiddenLabel()
                    //     ->formatStateUsing(fn($state) => new \Illuminate\Support\HtmlString($state)),
                    // ->columnSpanFull()
                ])->columns(1),
            Section::make('Bukti Fisik Kas')
                ->schema([
                    // Kalau langsung mau gambar
                    ImageEntry::make('cash_images.image')
                        ->label('Gambar')
                        ->getStateUsing(fn($record) => $record->cash_images->map(
                            fn($img) => asset('storage/upload/cash_mutates/' . $img->image)
                        ))
                        ->hiddenLabel()

                    // Atau kalau mau full custom view
                    // ViewEntry::make('proof')
                    //     ->view('filament.tables.images-modal')
                    //     ->viewData([
                    //         'folder' => 'sparepart_deposit',

                    //     ]),
                ])->columns(1)


        ]);
    }



    public function table(Table $table): Table
    {
        return $table
            ->query(
                \App\Models\ApprovalMutateCash::query()->where('cash_mutates_id', $this->record->id)
            )
            ->columns([
                TextColumn::make('user.name')->label('User'),
                TextColumn::make('description')->label('Deskripsi'),
                TextColumn::make('created_at')->label('Waktu')->dateTime('d/m/Y H:i'),
            ])->paginated(false);
    }
}
