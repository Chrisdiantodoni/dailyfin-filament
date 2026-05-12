<?php

namespace App\Filament\Resources\CashMutates\Schemas;

use App\Models\DealerUser;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Filament\Tables\Table;
use Illuminate\Console\View\Components\BulletList;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use Tiptap\Nodes\BulletList as NodesBulletList;
use Tiptap\Nodes\OrderedList;
use Tiptap\Nodes\Table as NodesTable;

class CashMutateForm
{

    // Dalam Class CashMutateForm
    public function cleanAndCalculate($state, $denomination)
    {
        // Membersihkan input dari non-numerik dan mengonversinya menjadi integer
        $numericState = (int) preg_replace('/[^0-9]/', '', $state);
        return $numericState * $denomination;
    }

    public function calculateTotal($get)
    {
        $denominations = [
            '100k' => 100000,
            '75k' => 75000,
            '50k' => 50000,
            '20k' => 20000,
            '10k' => 10000,
            '5k' => 5000,
            '2k' => 2000,
            '1k' => 1000,
            '500' => 500,
            '200' => 200,
            '100' => 100
        ];
        $total = 0;
        foreach ($denominations as $key => $value) {
            $denom = (int) preg_replace('/[^0-9]/', '', $get('denom_' . $key));
            $total += $denom * $value;
        }
        return number_format($total, 0, ',', '.');
    }
    public static function configure(Schema $schema): Schema
    {


        return $schema
            ->components([
                Grid::make([
                    'default' => 1,
                    'sm' => 1,
                    'xl' => 3,
                    'md' => 1
                ])
                    ->schema([
                        DatePicker::make('date_published')
                            ->label('Tanggal')
                            ->dehydrated()
                            ->default(fn() => Carbon::now())
                            ->minDate(getRole() == 'Finance Operation' ? Carbon::today() : null),
                        TextInput::make('expense')
                            ->mask(RawJs::make('$money($input, \',\', \'.\')'))
                            ->label('Pengeluaran')
                            ->live(onBlur: true)

                            ->stripCharacters(".")
                            ->reactive(),
                        TextInput::make('dealer_display')
                            ->label('Dealer')
                            ->default(fn() => Auth::user()->dealer_users->first()->dealers->dealer_name)
                            ->hidden(fn() => Auth::user()->dealer_users->count() > 1)
                            ->disabled()
                            ->dehydrated(false),

                        // Hidden field untuk simpan dealer_code
                        Hidden::make('dealer_code_single')
                            ->default(fn() => Auth::user()->dealer_users->first()->dealers->dealer_code)
                            ->dehydrated(),
                        Select::make('dealer_code')
                            ->required()
                            ->label('Dealer')
                            ->relationship(
                                'dealers',
                                'dealer_name',
                                fn($query) =>
                                $query->whereIn('dealer_code', DealerUser::where('user_id', Auth::id())->pluck('dealer_code'))->limit(5)
                            )
                            ->preload()
                            ->default(null)
                            ->searchable()
                            ->placeholder('Pilih Dealer')
                            ->hidden(fn() => Auth::user()->dealer_users->count() === 1)
                            ->live(),

                    ])->columnSpanFull(),
                Grid::make([
                    'default' => 1,
                    'sm' => 1,
                    'xl' => 3,
                    'md' => 1
                ])
                    ->schema([
                        TextInput::make('start_balance')
                            ->label('Saldo Awal')->dehydrated()
                            ->mask(RawJs::make('$money($input, \',\', \'.\')'))
                            ->live(onBlur: true)
                            ->stripCharacters(".")
                            ->extraAttributes([
                                'id' => 'today_income'
                            ]),
                        TextInput::make('invoice_nominal')
                            ->mask(RawJs::make('$money($input, \',\', \'.\')'))
                            ->label('Kasbon Gantung')
                            ->live()
                            ->reactive()
                            ->stripCharacters(".")
                            ->extraAttributes([
                                'id' => 'end_balance'
                            ])
                            ->helperText(function ($state) {
                                $numeric = (int) str_replace('.', '', $state);
                                if ($numeric > 0) {
                                    return new HtmlString(
                                        '<span style="color: #e53935;"> ⚠️ Nominal dan Rincian Bon Gantung Wajib diisi di Keterangan</span>'
                                    );
                                }
                                return null;
                            }),
                        TextInput::make('physical_cash')
                            ->mask(RawJs::make('$money($input, \',\', \'.\')'))
                            ->label('Fisik Kas')
                            ->live(onBlur: true)

                            ->extraAttributes([
                                'id' => 'end_balance'
                            ])
                            ->stripCharacters(".")
                            ->reactive(),

                    ])->columnSpanFull(),
                Grid::make([
                    'default' => 1,
                    'sm' => 1,
                    'xl' => 3,
                    'md' => 1
                ])
                    ->schema([
                        TextInput::make('income')
                            ->label('Penerimaan')->dehydrated()
                            ->mask(RawJs::make('$money($input, \',\', \'.\')'))
                            ->live(onBlur: true)
                            ->stripCharacters(".")
                            ->extraAttributes([
                                'id' => 'today_income'
                            ]),
                        TextInput::make('end_balance')
                            ->mask(RawJs::make('$money($input, \',\', \'.\')'))
                            ->label('Saldo akhir')
                            ->live(onBlur: true)

                            ->extraAttributes([
                                'id' => 'end_balance'
                            ])
                            ->stripCharacters(".")
                            ->reactive(),
                        FileUpload::make('cash_images')
                            ->label('Upload Bukti Fisik Kas')
                            ->disk('public')
                            ->image()
                            ->multiple()
                            ->panelLayout('grid')
                            // Jika tujuannya ingin menyimpan path lengkap agar bisa diakses:
                            ->mutateDehydratedStateUsing(function ($state) {
                                if (!$state) return [];
                                // Pastikan menyimpan path lengkap relatif terhadap disk 'public'
                                return collect($state)->values()->toArray();
                            })->required()
                            ->extraInputAttributes([
                                "x-on:livewire-upload-start" => "\$dispatch('file-upload-started')",
                                "x-on:livewire-upload-finish" => "\$dispatch('file-upload-finished')",
                                "x-on:livewire-upload-error" => "\$dispatch('file-upload-error')",
                            ]),
                        // FileUpload::make('cash_images')
                        //     ->label('Upload Foto Fisik Kas')
                        //     ->disk('public')
                        //     ->image()          // cuma gambar
                        //     ->multiple()       // bisa upload banyak
                        //     ->directory('upload/cash_mutates')
                        //     ->panelLayout('grid')
                        //     ->reactive()
                        //     ->extraInputAttributes([
                        //         "x-on:livewire-upload-start" => "\$dispatch('file-upload-started')",
                        //         "x-on:livewire-upload-finish" => "\$dispatch('file-upload-finished')",
                        //         "x-on:livewire-upload-error" => "\$dispatch('file-upload-error')",

                        //     ])
                        //     ->required()
                        //     ->live()
                        //     ->mutateDehydratedStateUsing(
                        //         fn($state) =>
                        //         collect($state)->map(fn($path) => basename($path))->toArray()
                        //     ),
                    ])->columnSpanFull(),
                Grid::make([
                    'default' => 2,
                    'sm' => 1,
                    'md' => 1,
                    'xl' => 2,
                ])
                    ->schema([
                        Section::make('Rincian Fisik Kas')
                            ->extraAttributes([
                                'class' => 'flex w-full h-full grid'
                            ])
                            ->schema([
                                Grid::make([
                                    'default' => 1,
                                    'lg' => 2,
                                ])->schema([
                                        Grid::make(1)
                                            ->extraAttributes([
                                                'class' => 'lg:mr-5 mr-0'
                                            ])
                                            ->schema([
                                                Grid::make(12)
                                                    ->schema([
                                                        TextInput::make('denom_100k')
                                                            ->label('Nominal')->hiddenLabel()
                                                            ->columnSpan(3)
                                                            ->live(onBlur: true)
                                                            ->suffix("lbr")->stripCharacters('.')
                                                            ->mask(RawJs::make('$money($input, \',\', \'.\')'))
                                                            ->afterStateUpdated(function ($state, $set, $get) {
                                                                $numeric = (int) preg_replace('/[^0-9]/', '', $state);
                                                                $subtotal = $numeric * 100000;
                                                                $form = new CashMutateForm();
                                                                $set('sub_total_100k', number_format($subtotal, 0, ',', '.'));
                                                                $set('total_cash', $form->calculateTotal($get));
                                                            }),
                                                        TextEntry::make('multiply_100k')->label('x')
                                                            ->columnSpan(1)
                                                            ->state("x")->hiddenLabel()->extraAttributes([
                                                                'class' => 'flex w-full h-[36px] items-center justify-center'
                                                            ]),
                                                        TextEntry::make('cash_denomination_100k')->label('x')
                                                            ->columnSpan(3)
                                                            ->state("Rp 100.000")->hiddenLabel()->extraAttributes([
                                                                'class' => 'flex w-full h-[36px] items-center justify-center'
                                                            ]),
                                                        TextEntry::make('equals_to_100k')->label('=')
                                                            ->columnSpan(1)
                                                            ->state("=")->hiddenLabel()->extraAttributes([
                                                                'class' => 'flex w-full h-[36px] items-center justify-center'
                                                            ]),
                                                        TextInput::make('sub_total_100k')->label('Rp.0')
                                                            ->disabled()
                                                            ->dehydrated()->default('0')
                                                            ->prefix('Rp. ')
                                                            ->columnSpan(4)->hiddenLabel()->extraAttributes([
                                                                'class' => 'flex w-full h-[36px] items-center justify-center'
                                                            ]),
                                                    ]),

                                                // Rp 75.000
                                                Grid::make(12)
                                                    ->schema([
                                                        TextInput::make('denom_75k')
                                                            ->label('Nominal')->hiddenLabel()
                                                            ->columnSpan(3)
                                                            ->dehydrated()
                                                            ->live(onBlur: true)
                                                            ->mask(RawJs::make('$money($input, \',\', \'.\')'))
                                                            ->suffix("lbr")->stripCharacters('.')
                                                            ->afterStateUpdated(function ($state, $set, $get) {
                                                                $numeric = (int) preg_replace('/[^0-9]/', '', $state);
                                                                $subtotal = $numeric  * 75000;
                                                                $form = new CashMutateForm();

                                                                $set('sub_total_75k', number_format($subtotal, 0, ',', '.'));
                                                                $set('total_cash', $form->calculateTotal($get));
                                                            }),
                                                        TextEntry::make('multiply_75k')->label('x')
                                                            ->columnSpan(1)
                                                            ->state("x")->hiddenLabel()->extraAttributes([
                                                                'class' => 'flex w-full h-[36px] items-center justify-center'
                                                            ]),
                                                        TextEntry::make('cash_denomination_75k')->label('x')
                                                            ->columnSpan(3)
                                                            ->state("Rp 75.000")->hiddenLabel()->extraAttributes([
                                                                'class' => 'flex w-full h-[36px] items-center justify-center'
                                                            ]),
                                                        TextEntry::make('equals_to_75k')->label('=')
                                                            ->columnSpan(1)
                                                            ->state("=")->hiddenLabel()->extraAttributes([
                                                                'class' => 'flex w-full h-[36px] items-center justify-center'
                                                            ]),
                                                        TextInput::make('sub_total_75k')->label('Rp.0')
                                                            ->prefix("Rp.")
                                                            ->disabled()
                                                            ->dehydrated()->default('0')
                                                            ->columnSpan(4)->hiddenLabel()->extraAttributes([
                                                                'class' => 'flex w-full h-[36px] items-center justify-center'
                                                            ]),
                                                    ]),

                                                // Rp 50.000
                                                Grid::make(12)
                                                    ->schema([
                                                        TextInput::make('denom_50k')
                                                            ->label('Nominal')->hiddenLabel()
                                                            ->columnSpan(3)
                                                            ->live(onBlur: true)
                                                            ->suffix("lbr")->stripCharacters('.')
                                                            ->mask(RawJs::make('$money($input, \',\', \'.\')'))

                                                            ->afterStateUpdated(function ($state, $set, $get) {
                                                                $numeric = (int) preg_replace('/[^0-9]/', '', $state);
                                                                $subtotal = $numeric  * 50000;
                                                                $form = new CashMutateForm();

                                                                $set('sub_total_50k', number_format($subtotal, 0, ',', '.'));
                                                                // $set('total_cash', $form->calculateTotal($get));
                                                            }),
                                                        TextEntry::make('multiply_50k')->label('x')
                                                            ->columnSpan(1)
                                                            ->state("x")->hiddenLabel()->extraAttributes([
                                                                'class' => 'flex w-full h-[36px] items-center justify-center'
                                                            ]),
                                                        TextEntry::make('cash_denomination_50k')->label('x')
                                                            ->columnSpan(3)
                                                            ->state("Rp 50.000")->hiddenLabel()->extraAttributes([
                                                                'class' => 'flex w-full h-[36px] items-center justify-center'
                                                            ]),
                                                        TextEntry::make('equals_to_50k')->label('=')
                                                            ->columnSpan(1)
                                                            ->state("=")->hiddenLabel()->extraAttributes([
                                                                'class' => 'flex w-full h-[36px] items-center justify-center'
                                                            ]),
                                                        TextInput::make('sub_total_50k')->label('Rp.0')
                                                            ->prefix("Rp.")
                                                            ->disabled()
                                                            ->dehydrated()->default('0')

                                                            ->columnSpan(4)->hiddenLabel()->extraAttributes([
                                                                'class' => 'flex w-full h-[36px] items-center justify-center'
                                                            ]),
                                                    ]),

                                                // Rp 20.000
                                                Grid::make(12)
                                                    ->schema([
                                                        TextInput::make('denom_20k')
                                                            ->label('Nominal')->hiddenLabel()
                                                            ->columnSpan(3)
                                                            ->live(onBlur: true)
                                                            ->suffix("lbr")->stripCharacters('.')
                                                            ->mask(RawJs::make('$money($input, \',\', \'.\')'))

                                                            ->afterStateUpdated(function ($state, $set, $get) {
                                                                $numeric = (int) preg_replace('/[^0-9]/', '', $state);
                                                                $subtotal = $numeric  * 20000;
                                                                $form = new CashMutateForm();

                                                                $set('sub_total_20k', number_format($subtotal, 0, ',', '.'));
                                                                // $set('total_cash', $form->calculateTotal($get));
                                                            }),
                                                        TextEntry::make('multiply_20k')->label('x')
                                                            ->columnSpan(1)
                                                            ->state("x")->hiddenLabel()->extraAttributes([
                                                                'class' => 'flex w-full h-[36px] items-center justify-center'
                                                            ]),
                                                        TextEntry::make('cash_denomination_20k')->label('x')
                                                            ->columnSpan(3)
                                                            ->state("Rp 20.000")->hiddenLabel()->extraAttributes([
                                                                'class' => 'flex w-full h-[36px] items-center justify-center'
                                                            ]),
                                                        TextEntry::make('equals_to_20k')->label('=')
                                                            ->columnSpan(1)
                                                            ->state("=")->hiddenLabel()->extraAttributes([
                                                                'class' => 'flex w-full h-[36px] items-center justify-center'
                                                            ]),
                                                        TextInput::make('sub_total_20k')->label('Rp.0')
                                                            ->prefix("Rp.")
                                                            ->disabled()
                                                            ->dehydrated()->default('0')
                                                            ->columnSpan(4)->hiddenLabel()->extraAttributes([
                                                                'class' => 'flex w-full h-[36px] items-center justify-center'
                                                            ]),
                                                    ]),

                                                // Rp 10.000
                                                Grid::make(12)
                                                    ->schema([
                                                        TextInput::make('denom_10k')
                                                            ->label('Nominal')->hiddenLabel()
                                                            ->columnSpan(3)
                                                            ->live(onBlur: true)
                                                            ->mask(RawJs::make('$money($input, \',\', \'.\')'))
                                                            ->suffix("lbr")->stripCharacters('.')

                                                            ->afterStateUpdated(function ($state, $set, $get) {
                                                                $numeric = (int) preg_replace('/[^0-9]/', '', $state);
                                                                $subtotal = $numeric  * 10000;
                                                                $form = new CashMutateForm();
                                                                Log::info($state . " 10k");
                                                                $set('sub_total_10k', number_format($subtotal, 0, ',', '.'));
                                                                // $set('total_cash', $form->calculateTotal($get));
                                                            }),
                                                        TextEntry::make('multiply_10k')->label('x')
                                                            ->columnSpan(1)
                                                            ->state("x")->hiddenLabel()->extraAttributes([
                                                                'class' => 'flex w-full h-[36px] items-center justify-center'
                                                            ]),
                                                        TextEntry::make('cash_denomination_10k')->label('x')
                                                            ->columnSpan(3)
                                                            ->state("Rp 10.000")->hiddenLabel()->extraAttributes([
                                                                'class' => 'flex w-full h-[36px] items-center justify-center'
                                                            ]),
                                                        TextEntry::make('equals_to_10k')->label('=')
                                                            ->columnSpan(1)
                                                            ->state("=")->hiddenLabel()->extraAttributes([
                                                                'class' => 'flex w-full h-[36px] items-center justify-center'
                                                            ]),
                                                        TextInput::make('sub_total_10k')->label('Rp.0')
                                                            ->prefix("Rp.")
                                                            ->disabled()
                                                            ->dehydrated()->default('0')
                                                            ->columnSpan(4)->hiddenLabel()->extraAttributes([
                                                                'class' => 'flex w-full h-[36px] items-center justify-center'
                                                            ]),
                                                    ]),
                                                Grid::make(12)
                                                    ->schema([
                                                        TextInput::make('denom_5k')
                                                            ->label('Nominal')->hiddenLabel()
                                                            ->columnSpan(3)
                                                            ->live(onBlur: true)
                                                            ->mask(RawJs::make('$money($input, \',\', \'.\')'))
                                                            ->suffix("lbr")->stripCharacters('.')

                                                            ->afterStateUpdated(function ($state, $set, $get) {
                                                                $numeric = (int) preg_replace('/[^0-9]/', '', $state);
                                                                $subtotal = $numeric  * 5000;
                                                                $form = new CashMutateForm();
                                                                Log::info($state . " 5k");

                                                                $set('sub_total_5k', number_format($subtotal, 0, ',', '.'));
                                                                $set('total_cash', $form->calculateTotal($get));
                                                            }),
                                                        TextEntry::make('multiply_5k')->label('x')
                                                            ->columnSpan(1)
                                                            ->state("x")->hiddenLabel()->extraAttributes([
                                                                'class' => 'flex w-full h-[36px] items-center justify-center'
                                                            ]),
                                                        TextEntry::make('cash_denomination_5k')->label('x')
                                                            ->columnSpan(3)
                                                            ->state("Rp 5.000")->hiddenLabel()->extraAttributes([
                                                                'class' => 'flex w-full h-[36px] items-center justify-center'
                                                            ]),
                                                        TextEntry::make('equals_to_5k')->label('=')
                                                            ->columnSpan(1)
                                                            ->state("=")->hiddenLabel()->extraAttributes([
                                                                'class' => 'flex w-full h-[36px] items-center justify-center'
                                                            ]),
                                                        TextInput::make('sub_total_5k')->label('Rp.0')
                                                            ->prefix("Rp.")
                                                            ->disabled()
                                                            ->dehydrated()->default('0')
                                                            ->columnSpan(4)->hiddenLabel()->extraAttributes([
                                                                'class' => 'flex w-full h-[36px] items-center justify-center'
                                                            ]),
                                                    ]),
                                            ]),
                                        Grid::make(1)
                                            ->extraAttributes([
                                                'class' => 'lg:ml-5 ml:0'
                                            ])
                                            ->schema([


                                                // Rp 2.000
                                                Grid::make(12)
                                                    ->schema([
                                                        TextInput::make('denom_2k')
                                                            ->label('Nominal')->hiddenLabel()
                                                            ->columnSpan(3)
                                                            ->live(onBlur: true)
                                                            ->mask(RawJs::make('$money($input, \',\', \'.\')'))
                                                            ->suffix("lbr")->stripCharacters('.')

                                                            ->afterStateUpdated(function ($state, $set, $get) {
                                                                $numeric = (int) preg_replace('/[^0-9]/', '', $state);
                                                                $subtotal = $numeric  * 2000;
                                                                $form = new CashMutateForm();

                                                                $set('sub_total_2k', number_format($subtotal, 0, ',', '.'));
                                                                $set('total_cash', $form->calculateTotal($get));
                                                            }),
                                                        TextEntry::make('multiply_2k')->label('x')
                                                            ->columnSpan(1)
                                                            ->state("x")->hiddenLabel()->extraAttributes([
                                                                'class' => 'flex w-full h-[36px] items-center justify-center'
                                                            ]),
                                                        TextEntry::make('cash_denomination_2k')->label('x')
                                                            ->columnSpan(3)
                                                            ->state("Rp 2.000")->hiddenLabel()->extraAttributes([
                                                                'class' => 'flex w-full h-[36px] items-center justify-center'
                                                            ]),
                                                        TextEntry::make('equals_to_2k')->label('=')
                                                            ->columnSpan(1)
                                                            ->state("=")->hiddenLabel()->extraAttributes([
                                                                'class' => 'flex w-full h-[36px] items-center justify-center'
                                                            ]),
                                                        TextInput::make('sub_total_2k')->label('Rp.0')
                                                            ->prefix("Rp.")
                                                            ->disabled()
                                                            ->dehydrated()->default('0')
                                                            ->columnSpan(4)->hiddenLabel()->extraAttributes([
                                                                'class' => 'flex w-full h-[36px] items-center justify-center'
                                                            ]),
                                                    ]),

                                                // Rp 1.000
                                                Grid::make(12)
                                                    ->schema([
                                                        TextInput::make('denom_1k')
                                                            ->label('Nominal')->hiddenLabel()
                                                            ->columnSpan(3)
                                                            ->live(onBlur: true)
                                                            ->mask(RawJs::make('$money($input, \',\', \'.\')'))
                                                            ->suffix("lbr")->stripCharacters('.')
                                                            ->afterStateUpdated(function ($state, $set, $get) {
                                                                $numeric = (int) preg_replace('/[^0-9]/', '', $state);
                                                                $subtotal = $numeric  * 1000;
                                                                $form = new CashMutateForm();

                                                                $set('sub_total_1k', number_format($subtotal, 0, ',', '.'));
                                                                $set('total_cash', $form->calculateTotal($get));
                                                            }),
                                                        TextEntry::make('multiply_1k')->label('x')
                                                            ->columnSpan(1)
                                                            ->state("x")->hiddenLabel()->extraAttributes([
                                                                'class' => 'flex w-full h-[36px] items-center justify-center'
                                                            ]),
                                                        TextEntry::make('cash_denomination_1k')->label('x')
                                                            ->columnSpan(3)
                                                            ->state("Rp 1.000")->hiddenLabel()->extraAttributes([
                                                                'class' => 'flex w-full h-[36px] items-center justify-center'
                                                            ]),
                                                        TextEntry::make('equals_to_1k')->label('=')
                                                            ->columnSpan(1)
                                                            ->state("=")->hiddenLabel()->extraAttributes([
                                                                'class' => 'flex w-full h-[36px] items-center justify-center'
                                                            ]),
                                                        TextInput::make('sub_total_1k')->label('Rp.0')
                                                            ->prefix("Rp.")
                                                            ->disabled()
                                                            ->dehydrated()->default('0')
                                                            ->columnSpan(4)->hiddenLabel()->extraAttributes([
                                                                'class' => 'flex w-full h-[36px] items-center justify-center'
                                                            ]),
                                                    ]),

                                                // Rp 500
                                                Grid::make(12)
                                                    ->schema([
                                                        TextInput::make('denom_500')
                                                            ->label('Nominal')->hiddenLabel()
                                                            ->columnSpan(3)
                                                            ->live(onBlur: true)
                                                            ->mask(RawJs::make('$money($input, \',\', \'.\')'))
                                                            ->suffix("lbr")->stripCharacters('.')

                                                            ->afterStateUpdated(function ($state, $set, $get) {
                                                                $numeric = (int) preg_replace('/[^0-9]/', '', $state);
                                                                $subtotal = $numeric  * 500;
                                                                $form = new CashMutateForm();

                                                                $set('sub_total_500', number_format($subtotal, 0, ',', '.'));
                                                                $set('total_cash', $form->calculateTotal($get));
                                                            }),
                                                        TextEntry::make('multiply_500')->label('x')
                                                            ->columnSpan(1)
                                                            ->state("x")->hiddenLabel()->extraAttributes([
                                                                'class' => 'flex w-full h-[36px] items-center justify-center'
                                                            ]),
                                                        TextEntry::make('cash_denomination_500')->label('x')
                                                            ->columnSpan(3)
                                                            ->state("Rp 500")->hiddenLabel()->extraAttributes([
                                                                'class' => 'flex w-full h-[36px] items-center justify-center'
                                                            ]),
                                                        TextEntry::make('equals_to_500')->label('=')
                                                            ->columnSpan(1)
                                                            ->state("=")->hiddenLabel()->extraAttributes([
                                                                'class' => 'flex w-full h-[36px] items-center justify-center'
                                                            ]),
                                                        TextInput::make('sub_total_500')->label('Rp.0')
                                                            ->prefix("Rp.")
                                                            ->disabled()
                                                            ->dehydrated()->default('0')
                                                            ->columnSpan(4)->hiddenLabel()->extraAttributes([
                                                                'class' => 'flex w-full h-[36px] items-center justify-center'
                                                            ]),
                                                    ]),

                                                // Rp 200
                                                Grid::make(12)
                                                    ->schema([
                                                        TextInput::make('denom_200')
                                                            ->label('Nominal')->hiddenLabel()
                                                            ->columnSpan(3)
                                                            ->live(onBlur: true)
                                                            ->mask(RawJs::make('$money($input, \',\', \'.\')'))
                                                            ->suffix("lbr")->stripCharacters('.')

                                                            ->afterStateUpdated(function ($state, $set, $get) {
                                                                $numeric = (int) preg_replace('/[^0-9]/', '', $state);
                                                                $subtotal = $numeric  * 200;
                                                                $form = new CashMutateForm();

                                                                $set('sub_total_200', number_format($subtotal, 0, ',', '.'));
                                                                $set('total_cash', $form->calculateTotal($get));
                                                            }),
                                                        TextEntry::make('multiply_200')->label('x')
                                                            ->columnSpan(1)
                                                            ->state("x")->hiddenLabel()->extraAttributes([
                                                                'class' => 'flex w-full h-[36px] items-center justify-center'
                                                            ]),
                                                        TextEntry::make('cash_denomination_200')->label('x')
                                                            ->columnSpan(3)
                                                            ->state("Rp 200")->hiddenLabel()->extraAttributes([
                                                                'class' => 'flex w-full h-[36px] items-center justify-center'
                                                            ]),
                                                        TextEntry::make('equals_to_200')->label('=')
                                                            ->columnSpan(1)
                                                            ->state("=")->hiddenLabel()->extraAttributes([
                                                                'class' => 'flex w-full h-[36px] items-center justify-center'
                                                            ]),
                                                        TextInput::make('sub_total_200')->label('Rp.0')
                                                            ->columnSpan(4)->prefix("Rp.")
                                                            ->disabled()
                                                            ->dehydrated()->default('0')->hiddenLabel()->extraAttributes([
                                                                'class' => 'flex w-full h-[36px] items-center justify-center'
                                                            ]),
                                                    ]),

                                                // Rp 100
                                                Grid::make(12)
                                                    ->schema([
                                                        TextInput::make('denom_100')
                                                            ->label('Nominal')->hiddenLabel()
                                                            ->columnSpan(3)
                                                            ->live(onBlur: true)
                                                            ->mask(RawJs::make('$money($input, \',\', \'.\')'))
                                                            ->suffix("lbr")->stripCharacters('.')

                                                            ->afterStateUpdated(function ($state, $set, $get) {
                                                                $numeric = (int) preg_replace('/[^0-9]/', '', $state);
                                                                $subtotal = $numeric  * 100;
                                                                $form = new CashMutateForm();

                                                                $set('sub_total_100', number_format($subtotal, 0, ',', '.'));
                                                                $set('total_cash', $form->calculateTotal($get));
                                                            }),
                                                        TextEntry::make('multiply_100')->label('x')
                                                            ->columnSpan(1)
                                                            ->state("x")->hiddenLabel()->extraAttributes([
                                                                'class' => 'flex w-full h-[36px] items-center justify-center'
                                                            ]),
                                                        TextEntry::make('cash_denomination_100')->label('x')
                                                            ->columnSpan(3)
                                                            ->state("Rp 100")->hiddenLabel()->extraAttributes([
                                                                'class' => 'flex w-full h-[36px] items-center justify-center'
                                                            ]),
                                                        TextEntry::make('equals_to_100')->label('=')
                                                            ->columnSpan(1)
                                                            ->state("=")->hiddenLabel()->extraAttributes([
                                                                'class' => 'flex w-full h-[36px] items-center justify-center'
                                                            ]),
                                                        TextInput::make('sub_total_100')->label('Rp.0')
                                                            ->prefix("Rp.")
                                                            ->disabled()
                                                            ->dehydrated()->default('0')
                                                            ->columnSpan(4)->hiddenLabel()->extraAttributes([
                                                                'class' => 'flex w-full h-[36px] items-center justify-center'
                                                            ]),
                                                    ]),
                                            ])
                                        // Rp 100.000


                                        // Rp 5.000


                                        // Total

                                    ])->columnSpanFull(),
                            ])
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                Grid::make([
                    'default' => 1,
                    'md' => 4,
                ])
                    ->schema([
                        TextEntry::make('total_label')
                            ->label('Total')
                            ->columnSpan(1)
                            ->state("TOTAL")->hiddenLabel()->extraAttributes([
                                'class' => 'flex w-full h-[36px] items-center  font-bold'
                            ]),
                        TextInput::make('total_cash')
                            ->label('Total Kas')
                            ->columnSpan(2)
                            ->prefix("Rp.")
                            ->disabled()
                            ->dehydrated()
                            ->stripCharacters(".")
                            ->hiddenLabel()->extraAttributes([
                                'class' => 'flex w-full h-[36px] items-center justify-center font-bold'
                            ]),
                    ]),
                Grid::make([
                    'default' => 1,
                    'sm' => 1,
                    'xl' => 3,
                    'md' => 1
                ])
                    ->schema([
                        RichEditor::make('description2')->label('Deskripsi')
                            ->toolbarButtons([
                                ['bold', 'italic', 'underline', 'strike', 'subscript', 'superscript', 'link'],
                                ['h2', 'h3', 'alignStart', 'alignCenter', 'alignEnd'],
                                ['blockquote', 'codeBlock', 'bulletList', 'orderedList'],
                                ['table'], // The `customBlocks` and `mergeTags` tools are also added here if those features are used.
                                ['undo', 'redo'],
                            ])

                            ->columnSpanFull(),


                    ])->columnSpanFull(),
            ]);
    }
}
