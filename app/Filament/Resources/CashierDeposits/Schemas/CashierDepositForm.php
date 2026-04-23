<?php

namespace App\Filament\Resources\CashierDeposits\Schemas;

use App\Models\CashierDeposit;
use App\Models\DealerUser;
use Carbon\Carbon;

use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CashierDepositForm
{
    private function getYesterdayDate()
    {
        $yesterday = Carbon::yesterday();
        if ($yesterday->isSunday()) {
            $yesterday = $yesterday->subDay();
        }
        return $yesterday;
    }
    // public function getBalance($dealer_code)
    // {
    //     $yesterday = $this->getYesterdayDate();
    //     $today_income = CashierDeposit::whereDate('date_published', '=', $yesterday)->where('status', 'approve')->where('dealer_code', $dealer_code)->first();
    //     $today_balance = CashierDeposit::whereDate('date_published', '=', $yesterday)->where('status', 'approve')->where('dealer_code', $dealer_code)->get();

    //     if ($today_income) {
    //         $end_balance = ($today_income->start_balance - $today_balance->sum('expense') - $today_balance->sum('bank_deposit') + $today_balance->sum('today_income') - $today_income->invoice);
    //     } else {
    //         $end_balance = 0;
    //     }
    //     return $end_balance;
    // }

    public function getBalance($dealer_code)
    {

        $latest_date = CashierDeposit::where('dealer_code', $dealer_code)
            ->max('date_published');

        // Retrieve the record for the latest date
        $latest_record_start_balance = CashierDeposit::where('dealer_code', $dealer_code)
            ->whereDate('date_published', '=', $latest_date)
            ->where('status', 'approve')
            ->first();
        $latest_record = CashierDeposit::where('dealer_code', $dealer_code)
            ->whereDate('date_published', '=', $latest_date)
            ->where('status', 'approve')
            ->get();

        if ($latest_record) {
            $end_balance = (($latest_record_start_balance->start_balance ?? 0) -
                $latest_record->sum('expense') -
                $latest_record->sum('bank_deposit') +
                $latest_record->sum('today_income'));
        } else {
            $end_balance = 0;
        }


        return $end_balance;
    }


    // public function getBalanceCoordinator($dealer_code, $request_date)
    // {
    //     $requested_date = $request_date; // Assuming the requested date is in YYYY-MM-DD format
    //     // Calculate yesterday's date based on the requested date
    //     $yesterday = Carbon::createFromFormat('Y-m-d', $requested_date)->subDay();

    //     // Retrieve today's income for the requested date
    //     $today_income = CashierDeposit::whereDate('date_published', '=', $yesterday)
    //         ->where('status', 'approve')
    //         ->where('dealer_code', $dealer_code)
    //         ->first();
    //     // dd($today_income);

    //     // Retrieve all transactions for yesterday
    //     $today_balance = CashierDeposit::whereDate('date_published', '=', $yesterday)
    //         ->where('status', 'approve')
    //         ->where('dealer_code', $dealer_code)
    //         ->get();

    //     if ($today_income) {
    //         // Calculate end balance based on yesterday's date
    //         $end_balance = ($today_income->start_balance - $today_balance->sum('expense') - $today_balance->sum('bank_deposit') + $today_balance->sum('today_income') - $today_income->invoice);
    //     } else {
    //         $end_balance = 0;
    //     }
    //     return $end_balance;
    // }

    public static function calculateEndBalance($start_balance, $today_income, $expense, $bank_deposit)
    {
        $end_balance = ($start_balance + $today_income) - $expense - $bank_deposit;
        return $end_balance;
    }

    public static function calculateTotalDeposit($end_balance, $invoice_nominal)
    {
        $total_deposit = $end_balance - $invoice_nominal;
        return $total_deposit;
    }

    public static function syncBalances($get, $set)
    {
        $start_balance   = (int) str_replace('.', '', $get('start_balance')) ?: 0;
        $today_income    = (int) str_replace('.', '', $get('today_income')) ?: 0;
        $expense         = (int) str_replace('.', '', $get('expense')) ?: 0;
        $bank_deposit    = (int) str_replace('.', '', $get('bank_deposit')) ?: 0;
        $invoice_nominal = (int) str_replace('.', '', $get('invoice_nominal')) ?: 0;

        $end_balance = self::calculateEndBalance(
            $start_balance,
            $today_income,
            $expense,
            $bank_deposit
        );
        $set('end_balance', number_format($end_balance, 0, ',', '.'));

        $total_deposit = self::calculateTotalDeposit($end_balance, $invoice_nominal);
        $set('total_deposit', number_format($total_deposit, 0, ',', '.'));
    }

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
                            ->minDate(getRole() == 'Cashier' ? Carbon::today() : null),
                        TextInput::make('name')
                            ->label('Nama')
                            ->default(fn() => Auth::user()->name)
                            ->readOnly(),
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
                    'default' => 3,
                    'sm' => 1,
                    'xl' => 3,
                    'md' => 1
                ])
                    ->schema([
                        TextInput::make('start_balance')
                            ->label('Saldo Awal')->dehydrated()
                            ->mask(RawJs::make('$money($input, \',\', \'.\')'))
                            ->suffixAction(

                                Action::make('refresh-balance')
                                    ->icon('heroicon-o-arrow-path') // Icon refresh
                                    ->tooltip('Sync Saldo Awal')
                                    ->action(function (Component $component, $get, $set) {
                                        // Logic untuk refresh saldo
                                        $dealerCode = $get('dealer_code') ?? $get('dealer_code_single');

                                        $dates = $get('date_published');

                                        $form = new \App\Filament\Resources\CashierDeposits\Schemas\CashierDepositForm();
                                        if ($dealerCode) {
                                            // if (getRole() == 'Coordinator' || getRole() == 'IT') {
                                            //     $newBalance = $form->getBalanceCoordinator($dealerCode, $dates);
                                            //     $set('start_balance', number_format($newBalance, 0, ',', '.'));
                                            //     return;
                                            // }
                                            $newBalance = $form->getBalance($dealerCode);
                                            $set('start_balance',  number_format($newBalance, 0, ',', '.'));
                                            return;
                                        }
                                    })->hidden(fn($operation) => $operation === 'edit')
                            )->extraAttributes([
                                'id' => 'start_balance'
                            ])
                            ->live(onBlur: true)
                            ->stripCharacters(".")
                            ->afterStateUpdated(fn($get, $set) => CashierDepositForm::syncBalances($get, $set))
                            ->readOnly(fn($operation) => (isCoordinator() == false && $operation !== 'edit')),
                        TextInput::make('bank_deposit')
                            ->mask(RawJs::make('$money($input, \',\', \'.\')'))
                            ->label('Setoran ke Bank (Opsional)')
                            ->live(onBlur: true)
                            ->extraAttributes([
                                'id' => 'bank_deposit'
                            ])
                            ->stripCharacters(".")
                            ->afterStateUpdated(fn($get, $set) => CashierDepositForm::syncBalances($get, $set)),
                        TextInput::make('bank_name')
                            ->label('Masukkan Nama Bank (Opsional)')
                            ->helperText('Nama Bank dalam huruf besar (ex. BNI/BCA/BRI)')
                    ])->columnSpanFull(),
                Grid::make([
                    'default' => 3,
                    'sm' => 1,
                    'xl' => 3,
                    'md' => 1
                ])
                    ->schema([
                        TextInput::make('today_income')
                            ->label('Penerimaan Hari Ini')->dehydrated()
                            ->mask(RawJs::make('$money($input, \',\', \'.\')'))
                            ->afterStateUpdated(fn($get, $set) => CashierDepositForm::syncBalances($get, $set))
                            ->live(onBlur: true)
                            ->stripCharacters(".")
                            ->extraAttributes([
                                'id' => 'today_income'
                            ]),
                        TextInput::make('end_balance')
                            ->mask(RawJs::make('$money($input, \',\', \'.\')'))
                            ->label('Saldo akhir')
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn($get, $set) => CashierDepositForm::syncBalances($get, $set))

                            ->extraAttributes([
                                'id' => 'end_balance'
                            ])
                            ->stripCharacters(".")
                            ->readOnly()
                            ->reactive(),
                        FileUpload::make('cashier_images')
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
                        TextInput::make('expense')
                            ->label('Pengeluaran Hari Ini')->dehydrated()
                            ->mask(RawJs::make('$money($input, \',\', \'.\')'))
                            ->live(onBlur: true)
                            ->stripCharacters(".")
                            ->afterStateUpdated(fn($get, $set) => CashierDepositForm::syncBalances($get, $set))
                            ->extraAttributes(['id' => 'expense'])
                            ->helperText('Tidak Termasuk Setoran ke Bank'),

                        TextInput::make('invoice_nominal')
                            ->mask(RawJs::make('$money($input, \',\', \'.\')'))
                            ->label('Kasbon Gantung')
                            ->live(onBlur: true)
                            ->extraAttributes([
                                'id' => 'invoice_nominal'
                            ])
                            ->afterStateUpdated(fn($get, $set) => CashierDepositForm::syncBalances($get, $set))
                            ->stripCharacters("."),
                        TextInput::make('total_deposit')
                            ->mask(RawJs::make('$money($input, \',\', \'.\')'))
                            ->label('Total Setoran ke Brankas')
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn($get, $set) => CashierDepositForm::syncBalances($get, $set))
                            ->extraAttributes([
                                'id' => 'total_deposit'
                            ])
                            ->stripCharacters(".")
                            ->readOnly()
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
