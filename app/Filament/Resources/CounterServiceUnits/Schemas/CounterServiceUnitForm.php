<?php

namespace App\Filament\Resources\CounterServiceUnits\Schemas;

use App\Models\DealerUser;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;


class CounterServiceUnitForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
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
                        ->readOnly(),

                    TextInput::make('name')
                        ->label('Nama')
                        ->default(fn() => Auth::user()->name)
                        ->readOnly(),


                    TextInput::make('role')
                        ->label('Bagian')
                        ->default(fn() => Auth::user()->roles->first()->name)
                        ->readOnly(),

                ])->columnSpanFull(),
            Grid::make([
                'default' => 3,
                'sm' => 1,
                'xl' => 3,
                'md' => 1
            ])
                ->schema([

                    TextInput::make('unit_nominal_dtl.cash')
                        ->mask(RawJs::make('$money($input, \',\', \'.\')'))
                        ->stripCharacters(".")
                        ->label('Jumlah Setoran (Cash)')
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Get $get, Set $set, ?string $old, ?string $state) {
                            $cash =  (int) Str::replace('.', '', $state) ?: 0;
                            $expense = (int) Str::replace('.', '', $get('total_expense')) ?: 0;
                            $total = $cash - $expense;
                            $set('total_income', number_format($total, 0, ',', '.'));
                        }),

                    TextInput::make('unit_nominal_dtl.transfer')
                        ->mask(RawJs::make('$money($input, \',\', \'.\')'))
                        ->stripCharacters(".")
                        ->label('Jumlah Setoran (Transfer)')
                        ->stripCharacters('.')

                        ->numeric(),


                    FileUpload::make('unit_images_upload')
                        ->label('Upload Bukti Setoran (Optional)')
                        ->disk('public')
                        ->image()
                        ->multiple()
                        ->panelLayout('grid')
                        // Jika tujuannya ingin menyimpan path lengkap agar bisa diakses:
                        ->mutateDehydratedStateUsing(function ($state) {
                            if (!$state) return [];
                            // Pastikan menyimpan path lengkap relatif terhadap disk 'public'
                            return collect($state)->values()->toArray();
                        })
                        ->extraInputAttributes([
                            "x-on:livewire-upload-start" => "\$dispatch('file-upload-started')",
                            "x-on:livewire-upload-finish" => "\$dispatch('file-upload-finished')",
                            "x-on:livewire-upload-error" => "\$dispatch('file-upload-error')",
                        ]),

                ])->columnSpanFull(),

            Grid::make([
                'default' => 3,
                'sm' => 1,
                'xl' => 3,
                'md' => 1
            ])
                ->schema([
                    TextInput::make('total_expense')
                        ->mask(RawJs::make('$money($input, \',\', \'.\')'))
                        ->label('Total Pengeluaran')
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Get $get, Set $set, ?string $old, ?string $state) {
                            $expense = (int) Str::replace('.', '', $state) ?: 0;
                            $cash = (int) Str::replace('.', '', $get('service_nominal_dtls.cash')) ?: 0;

                            $total = $cash - $expense;
                            $set('total_income', number_format($total, 0, ',', '.'));
                        })
                        ->stripCharacters("."),


                    TextInput::make('total_income')
                        ->mask(RawJs::make('$money($input, \',\', \'.\')'))
                        ->label('Total Disetor')->dehydrated(true)
                        ->stripCharacters('.')
                        ->default(0)
                        ->readOnly(),

                    Select::make('dealer_code_single')
                        ->label('Dealer')->dehydrated()
                        ->default(fn() => Auth::user()->dealer_users->first()->dealers->dealer_name)
                        ->hidden(fn() => Auth::user()->dealer_users->count() > 1)
                        ->disabled(),
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
                        ->searchable()
                        ->placeholder('Pilih Dealer')
                        ->hidden(fn() => Auth::user()->dealer_users->count() === 1)
                        ->live(),
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
