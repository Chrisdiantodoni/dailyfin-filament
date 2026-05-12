<?php

namespace App\Filament\Resources\TakeoutMoney\Schemas;

use App\Models\CashierDeposit;
use App\Models\DealerUser;
use App\Support\UserDealerContext;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Illuminate\Support\Facades\Auth;

class TakeoutMoneyForm
{
    private function getYesterdayDate()
    {
        $yesterday = Carbon::yesterday();
        if ($yesterday->isSunday()) {
            $yesterday = $yesterday->subDay();
        }

        return $yesterday;
    }

    public function getBalance($dealer_code)
    {
        $latest_date = CashierDeposit::where('dealer_code', $dealer_code)
            ->max('date_published');

        if (! $latest_date) {
            return 0;
        }

        $latest_record = CashierDeposit::where('dealer_code', $dealer_code)
            ->where('date_published', $latest_date)
            ->where('status', 'approve')
            ->selectRaw('
                COUNT(*) as records_count,
                COALESCE(MAX(start_balance), 0) as start_balance,
                COALESCE(SUM(expense), 0) as expense_total,
                COALESCE(SUM(bank_deposit), 0) as bank_deposit_total,
                COALESCE(SUM(today_income), 0) as today_income_total,
                COALESCE(SUM(invoice), 0) as invoice_total
            ')
            ->first();

        if (! $latest_record || (int) $latest_record->records_count === 0) {
            return 0;
        }

        return (int) $latest_record->start_balance
            - (int) $latest_record->expense_total
            - (int) $latest_record->bank_deposit_total
            + (int) $latest_record->today_income_total
            - (int) $latest_record->invoice_total;
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Input Pengambilan Uang')
                    ->description('Lengkapi data pengambilan, nominal titipan, dan keterangan transaksi.')
                    ->schema([
                Grid::make([
                    'default' => 1,
                    'sm' => 1,
                    'xl' => 3,
                    'md' => 1,
                ])
                    ->schema([
                        DatePicker::make('date_published')
                            ->label('Tanggal')
                            ->dehydrated()
                            ->default(fn () => Carbon::now())
                            ->readOnly(fn ($operation) => $operation == 'edit')
                            ->minDate(getRole() == 'Cashier' ? Carbon::today() : null),
                        TextInput::make('name')
                            ->label('Nama')
                            ->default(fn () => Auth::user()->name)
                            ->readOnly(),
                        TextInput::make('dealer_display')
                            ->label('Dealer')
                            ->default(fn () => UserDealerContext::firstDealerName())
                            ->disabled()
                            ->hidden(fn () => UserDealerContext::hasMultipleDealers())

                            ->dehydrated(false),

                        // Hidden field untuk simpan dealer_code
                        Hidden::make('dealer_code_single')
                            ->default(fn () => UserDealerContext::firstDealerCode())
                            ->dehydrated(),
                        Select::make('dealer_code')
                            ->required()
                            ->label('Dealer')
                            ->relationship(
                                'dealers',
                                'dealer_name',
                                fn ($query) => $query->whereIn('dealer_code', DealerUser::where('user_id', Auth::id())->pluck('dealer_code'))->limit(5)
                            )
                            ->preload()
                            ->default(null)
                            ->searchable()
                            ->placeholder('Pilih Dealer')
                            ->hidden(fn () => UserDealerContext::hasSingleDealer())
                            ->live(),

                    ])->columnSpanFull(),
                Grid::make([
                    'default' => 1,
                    'sm' => 1,
                    'xl' => 3,
                    'md' => 1,
                ])
                    ->schema([
                        TextInput::make('end_balance')
                            ->label('Jumlah Uang di Brankas')->dehydrated()
                            ->mask(RawJs::make('$money($input, \',\', \'.\')'))
                            ->suffixAction(

                                Action::make('refresh-balance')
                                    ->icon('heroicon-o-arrow-path') // Icon refresh
                                    ->tooltip('Sync Saldo Awal')
                                    ->action(function (Component $component, $get, $set) {
                                        // Logic untuk refresh saldo
                                        $dealerCode = $get('dealer_code') ?? $get('dealer_code_single');
                                        $dates = $get('date_published');

                                        $form = new \App\Filament\Resources\TakeoutMoney\Schemas\TakeoutMoneyForm;
                                        if ($dealerCode) {
                                            // dd($dealerCode);
                                            // if (getRole() == 'Coordinator' || getRole() == 'IT') {
                                            //     $newBalance = $form->getBalanceCoordinator($dealerCode, $dates);
                                            //     $set('start_balance', number_format($newBalance, 0, ',', '.'));
                                            //     return;
                                            // }
                                            $newBalance = $form->getBalance($dealerCode);
                                            $set('end_balance', number_format($newBalance, 0, ',', '.'));

                                            return;
                                        } else {
                                            Notification::make()->title('Dealer Wajib dipilih')->send();

                                            return;
                                        }
                                    })->hidden(fn ($operation) => $operation === 'edit')
                            )->extraAttributes([
                                'id' => 'start_balance',
                            ])
                            ->helperText('Laporan Sore')
                            ->live(onBlur: true)
                            ->stripCharacters('.')
                            ->readOnly(fn ($operation) => (isCoordinator() == false && $operation !== 'edit')),
                        TextInput::make('money_put')
                            ->mask(RawJs::make('$money($input, \',\', \'.\')'))
                            ->label('Jumlah Uang Titipan')
                            ->live(onBlur: true)
                            ->extraAttributes([
                                'id' => 'bank_deposit',
                            ])
                            ->stripCharacters('.'),
                        TextInput::make('takeout_nominal')
                            ->mask(RawJs::make('$money($input, \',\', \'.\')'))
                            ->label('Total Uang Dikeluarkan')
                            ->live(onBlur: true)
                            ->readOnly(fn ($operation) => $operation == 'edit')
                            ->extraAttributes([
                                'id' => 'bank_deposit',
                            ])
                            ->stripCharacters('.'),
                    ])->columnSpanFull(),
                Grid::make([
                    'default' => 1,
                ])
                    ->schema([
                        TextInput::make('revised_ops_nominal')
                            ->mask(RawJs::make('$money($input, \',\', \'.\')'))
                            ->label('Revisi Jumlah Uang Dikeluarkan')
                            ->live(onBlur: true)
                            ->hidden(fn ($operation) => $operation != 'edit')
                            ->stripCharacters('.'),

                    ])->columnSpanFull()->hidden(fn ($operation) => $operation != 'edit'),
                Grid::make([
                    'default' => 1,
                ])
                    ->schema([
                        Textarea::make('description')
                            ->label('Keterangan')->columnSpanFull(),

                    ])->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
