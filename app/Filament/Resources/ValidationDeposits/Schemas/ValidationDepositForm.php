<?php

namespace App\Filament\Resources\ValidationDeposits\Schemas;

use App\Models\DealerUser;
use App\Models\Neq;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Illuminate\Support\Facades\Auth;

class ValidationDepositForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make([
                    'default' => 3,
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
                        TextInput::make('customer_name')
                            ->label('Nama Penyetor'),
                        TextInput::make('bank_name')
                            ->label('Nama Bank'),

                    ])->columnSpanFull(),
                Grid::make([
                    'default' => 3,
                    'sm' => 1,
                    'xl' => 3,
                    'md' => 1
                ])
                    ->schema([
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
                        Select::make('transaction_type')
                            ->required()->reactive()
                            ->label('Tipe Transaksi')
                            ->options([
                                'Setor Tunai dari MDS' => 'Setor Tunai dari MDS',
                                'Setor Tunai dari NEQ' => 'Setor Tunai dari NEQ',
                            ])->afterStateUpdated(fn(callable $set, $state) => $set('neq_name', null)),
                        Select::make('neq_name')
                            ->required(fn($get) => $get('transaction_type') == 'Setor Tunai dari NEQ')
                            ->label('NEQ')
                            ->disabled(fn($get) => $get('transaction_type') != 'Setor Tunai dari NEQ')
                            ->options(
                                fn($get) =>
                                Neq::where(
                                    'dealer_code',
                                    $get('dealer_code')
                                )->orWhere('dealer_code', $get('dealer_code_single'))
                                    ->pluck('neq_name', 'neq_name')
                            )
                            ->preload()
                            ->default(null)
                            ->searchable()
                            ->placeholder('Pilih NEQ')
                            ->live(),

                    ])->columnSpanFull(),
                Grid::make([
                    'default' => 3,
                    'sm' => 1,
                    'xl' => 3,
                    'md' => 1
                ])
                    ->schema([
                        TextInput::make('nominal_deposit')
                            ->label('Nominal Setoran')->dehydrated()
                            ->mask(RawJs::make('$money($input, \',\', \'.\')'))
                            ->live(onBlur: true)
                            ->stripCharacters(".")
                            ->extraAttributes([
                                'id' => 'today_income'
                            ]),
                        DatePicker::make('deposit_date')
                            ->label('Tanggal Setoran')
                            ->dehydrated()
                            ->default(fn() => Carbon::now()),
                        FileUpload::make('validate_images')
                            ->label('Upload Bukti Setoran')
                            ->disk('public')
                            ->image()          // cuma gambar
                            ->multiple()       // bisa upload banyak
                            ->directory('upload/validate')
                            ->panelLayout('grid')
                            ->reactive()
                            ->extraInputAttributes([
                                "x-on:livewire-upload-start" => "\$dispatch('file-upload-started')",
                                "x-on:livewire-upload-finish" => "\$dispatch('file-upload-finished')",
                                "x-on:livewire-upload-error" => "\$dispatch('file-upload-error')",

                            ])
                            ->required()
                            ->live()
                            ->mutateDehydratedStateUsing(
                                fn($state) =>
                                collect($state)->map(fn($path) => basename($path))->toArray()
                            ),
                    ])->columnSpanFull(),
                Grid::make([
                    'default' => 3,
                ])
                    ->schema([
                        Textarea::make('description')
                            ->label('Keterangan')->columnSpanFull(),

                    ])->columnSpanFull(),
            ]);
    }
}
