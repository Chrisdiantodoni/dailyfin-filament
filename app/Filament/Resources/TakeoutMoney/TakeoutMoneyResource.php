<?php

namespace App\Filament\Resources\TakeoutMoney;

use App\Filament\Pages\TakeoutDetail;
use App\Filament\Resources\TakeoutMoney\Pages\CreateTakeoutMoney;
use App\Filament\Resources\TakeoutMoney\Pages\EditTakeoutMoney;
use App\Filament\Resources\TakeoutMoney\Pages\ListTakeoutMoney;
use App\Filament\Resources\TakeoutMoney\Schemas\TakeoutMoneyForm;
use App\Filament\Resources\TakeoutMoney\Tables\TakeoutMoneyTable;
use App\Models\cashier_takeout_money;
use App\Models\TakeoutMoney;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class TakeoutMoneyResource extends Resource
{
    protected static ?string $model = cashier_takeout_money::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ArrowUpTray;

    protected static ?string $recordTitleAttribute = 'cashier_takeout_money';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationLabel = 'Keluar Uang Brankas';
    protected static string | UnitEnum | null $navigationGroup = 'Mutasi & Kas';

    public static function form(Schema $schema): Schema
    {
        return TakeoutMoneyForm::configure($schema);
    }


    public static function canAccess(): bool
    {
        return canAny([
            "Keluarkan Uang Kasir",
            "Report Keluarkan Uang",
            "Konfirmasi Keluarkan Uang",
            "Coordinator Resources"
        ]);
        // return can("Keluarkan Uang Kasir")
        //     || can("Report Keluarkan Uang")
        //     || can("Konfirmasi Keluarkan Uang")
        //     || can("Coordinator Resources");
    }

    public static function table(Table $table): Table
    {
        return TakeoutMoneyTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $dealerCodes = Auth::user()->dealer_users->pluck('dealers.dealer_code')->toArray();

        return parent::getEloquentQuery()
            ->whereIn('dealer_code', $dealerCodes)
            ->latest();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTakeoutMoney::route('/'),
            'create' => CreateTakeoutMoney::route('/create'),
            'edit' => EditTakeoutMoney::route('/{record}/edit'),
            'detail' => TakeoutDetail::route('/{record}'),
        ];
    }
}
