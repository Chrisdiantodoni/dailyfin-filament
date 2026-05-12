<?php

namespace App\Filament\Resources\CashMutates\Schemas;

use App\Models\DealerUser;
use App\Support\UserDealerContext;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class CashMutateForm
{
    private const CASH_DENOMINATIONS = [
        ['key' => '100k', 'label' => 'Rp 100.000', 'value' => 100000],
        ['key' => '75k', 'label' => 'Rp 75.000', 'value' => 75000],
        ['key' => '50k', 'label' => 'Rp 50.000', 'value' => 50000],
        ['key' => '20k', 'label' => 'Rp 20.000', 'value' => 20000],
        ['key' => '10k', 'label' => 'Rp 10.000', 'value' => 10000],
        ['key' => '5k', 'label' => 'Rp 5.000', 'value' => 5000],
        ['key' => '2k', 'label' => 'Rp 2.000', 'value' => 2000],
        ['key' => '1k', 'label' => 'Rp 1.000', 'value' => 1000],
        ['key' => '500', 'label' => 'Rp 500', 'value' => 500],
        ['key' => '200', 'label' => 'Rp 200', 'value' => 200],
        ['key' => '100', 'label' => 'Rp 100', 'value' => 100],
    ];

    public function calculateTotal($get): string
    {
        $total = 0;

        foreach (self::CASH_DENOMINATIONS as $denomination) {
            $key = $denomination['key'];
            $value = $denomination['value'];
            $denom = (int) preg_replace('/[^0-9]/', '', (string) $get("denom_{$key}"));
            $total += $denom * $value;
        }

        return number_format($total, 0, ',', '.');
    }

    private static function denominationRows(array $denominations): array
    {
        return array_map(
            fn (array $denomination): Grid => self::denominationRow(
                $denomination['key'],
                $denomination['label'],
                $denomination['value'],
            ),
            $denominations,
        );
    }

    private static function denominationCalculatorJs(string $key, int $value): string
    {
        $denominations = json_encode(
            collect(self::CASH_DENOMINATIONS)
                ->mapWithKeys(fn (array $denomination): array => [$denomination['key'] => $denomination['value']])
                ->all(),
            JSON_THROW_ON_ERROR
        );

        return <<<JS
(() => {
    const denominations = {$denominations};
    const digits = (input) => String(input ?? '').replace(/[^0-9]/g, '');
    const format = (amount) => new Intl.NumberFormat('id-ID').format(Number(amount || 0));
    const current = Number(digits(\$state));

    \$wire.set('data.sub_total_{$key}', format(current * {$value}), false);

    let total = 0;

    Object.entries(denominations).forEach(([denominationKey, denominationValue]) => {
        const rawValue = denominationKey === '{$key}'
            ? \$state
            : \$wire.get(`data.denom_\${denominationKey}`);

        total += Number(digits(rawValue)) * Number(denominationValue);
    });

    \$wire.set('data.total_cash', format(total), false);
})()
JS;
    }

    private static function denominationRow(string $key, string $label, int $value): Grid
    {
        return Grid::make([
            'default' => 1,
            'md' => 12,
        ])
            ->extraAttributes([
                'class' => 'df-cash-denomination-row',
            ])
            ->schema([
                TextInput::make("denom_{$key}")
                    ->label("Jumlah lembar {$label}")
                    ->hiddenLabel()
                    ->columnSpanFull()
                    ->extraAttributes([
                        'class' => 'df-cash-denomination-qty',
                    ])
                    ->live(debounce: 75)
                    ->suffix('lbr')
                    ->stripCharacters('.')
                    ->mask(RawJs::make('$money($input, \',\', \'.\')'))
                    ->afterStateUpdatedJs(self::denominationCalculatorJs($key, $value))
                    ->skipRenderAfterStateUpdated(),
                TextEntry::make("multiply_{$key}")
                    ->hiddenLabel()
                    ->state('x')
                    ->columnSpanFull()
                    ->extraAttributes([
                        'class' => 'df-cash-denomination-operator',
                    ]),
                TextEntry::make("cash_denomination_{$key}")
                    ->hiddenLabel()
                    ->state($label)
                    ->columnSpanFull()
                    ->extraAttributes([
                        'class' => 'df-cash-denomination-label',
                    ]),
                TextEntry::make("equals_to_{$key}")
                    ->hiddenLabel()
                    ->state('=')
                    ->columnSpanFull()
                    ->extraAttributes([
                        'class' => 'df-cash-denomination-operator',
                    ]),
                TextInput::make("sub_total_{$key}")
                    ->label("Subtotal {$label}")
                    ->hiddenLabel()
                    ->prefix('Rp.')
                    ->disabled()
                    ->dehydrated()
                    ->default('0')
                    ->columnSpanFull()
                    ->extraAttributes([
                        'class' => 'df-cash-denomination-subtotal',
                    ]),
            ]);
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Input Mutasi Kas')
                    ->description('Lengkapi data laporan, saldo, bukti fisik kas, dan keterangan.')
                    ->schema([
                        Grid::make([
                            'default' => 1,
                            'xl' => 3,
                        ])->schema([
                            DatePicker::make('date_published')
                                ->label('Tanggal')
                                ->dehydrated()
                                ->default(fn () => Carbon::now())
                                ->minDate(getRole() == 'Finance Operation' ? Carbon::today() : null),
                            TextInput::make('expense')
                                ->mask(RawJs::make('$money($input, \',\', \'.\')'))
                                ->label('Pengeluaran')
                                ->live(onBlur: true)
                                ->stripCharacters('.')
                                ->reactive(),
                            TextInput::make('dealer_display')
                                ->label('Dealer')
                                ->default(fn () => UserDealerContext::firstDealerName())
                                ->hidden(fn () => UserDealerContext::hasMultipleDealers())
                                ->disabled()
                                ->dehydrated(false),
                            Hidden::make('dealer_code_single')
                                ->default(fn () => UserDealerContext::firstDealerCode())
                                ->dehydrated(),
                            Select::make('dealer_code')
                                ->required()
                                ->label('Dealer')
                                ->relationship(
                                    'dealers',
                                    'dealer_name',
                                    fn ($query) => $query
                                        ->whereIn('dealer_code', DealerUser::where('user_id', Auth::id())->pluck('dealer_code'))
                                        ->limit(5)
                                )
                                ->preload()
                                ->default(null)
                                ->searchable()
                                ->placeholder('Pilih Dealer')
                                ->hidden(fn () => UserDealerContext::hasSingleDealer())
                                ->live(),
                            TextInput::make('start_balance')
                                ->label('Saldo Awal')
                                ->dehydrated()
                                ->mask(RawJs::make('$money($input, \',\', \'.\')'))
                                ->live(onBlur: true)
                                ->stripCharacters('.'),
                            TextInput::make('invoice_nominal')
                                ->mask(RawJs::make('$money($input, \',\', \'.\')'))
                                ->label('Kasbon Gantung')
                                ->live()
                                ->reactive()
                                ->stripCharacters('.')
                                ->helperText(function ($state) {
                                    $numeric = (int) str_replace('.', '', (string) $state);

                                    if ($numeric <= 0) {
                                        return null;
                                    }

                                    return new HtmlString(
                                        '<span style="color: #e53935;">Nominal dan rincian bon gantung wajib diisi di keterangan.</span>'
                                    );
                                }),
                            TextInput::make('physical_cash')
                                ->mask(RawJs::make('$money($input, \',\', \'.\')'))
                                ->label('Fisik Kas')
                                ->live(onBlur: true)
                                ->stripCharacters('.')
                                ->reactive(),
                            TextInput::make('income')
                                ->label('Penerimaan')
                                ->dehydrated()
                                ->mask(RawJs::make('$money($input, \',\', \'.\')'))
                                ->live(onBlur: true)
                                ->stripCharacters('.'),
                            TextInput::make('end_balance')
                                ->mask(RawJs::make('$money($input, \',\', \'.\')'))
                                ->label('Saldo Akhir')
                                ->live(onBlur: true)
                                ->stripCharacters('.')
                                ->reactive(),
                            FileUpload::make('cash_images')
                                ->label('Upload Bukti Fisik Kas')
                                ->disk(fn (): string => \App\Support\UploadStorage::temporaryDisk())
                                ->image()
                                ->multiple()
                                ->panelLayout('grid')
                                ->mutateDehydratedStateUsing(function ($state) {
                                    if (! $state) {
                                        return [];
                                    }

                                    return collect($state)->values()->toArray();
                                })
                                ->required()
                                ->extraInputAttributes([
                                    "x-on:livewire-upload-start" => "\$dispatch('file-upload-started')",
                                    "x-on:livewire-upload-finish" => "\$dispatch('file-upload-finished')",
                                    "x-on:livewire-upload-error" => "\$dispatch('file-upload-error')",
                                ]),
                        ]),
                        Textarea::make('description2')
                            ->label('Deskripsi')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                Section::make('Rincian Fisik Kas')
                    ->description('Isi jumlah lembar per pecahan. Format: jumlah lembar x nominal = total.')
                    ->extraAttributes([
                        'class' => 'df-cash-denomination-section',
                    ])
                    ->schema([
                        Grid::make([
                            'default' => 1,
                            'xl' => 2,
                        ])->schema([
                            Grid::make(1)
                                ->schema(self::denominationRows(array_slice(self::CASH_DENOMINATIONS, 0, 6))),
                            Grid::make(1)
                                ->schema(self::denominationRows(array_slice(self::CASH_DENOMINATIONS, 6))),
                        ]),
                        Grid::make([
                            'default' => 1,
                            'md' => 2,
                        ])->schema([
                            TextInput::make('total_cash')
                                ->label('Total Fisik Kas')
                                ->prefix('Rp.')
                                ->disabled()
                                ->dehydrated()
                                ->stripCharacters('.')
                                ->extraAttributes([
                                    'class' => 'df-cash-total-field',
                                ]),
                        ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
