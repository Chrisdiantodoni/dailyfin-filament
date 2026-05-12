<?php

namespace App\Filament\Resources\CashMutates;

use App\Filament\Pages\CashMutateDetail;
use App\Filament\Resources\CashMutates\Pages\CreateCashMutate;
use App\Filament\Resources\CashMutates\Pages\EditCashMutate;
use App\Filament\Resources\CashMutates\Pages\ListCashMutates;
use App\Filament\Resources\CashMutates\Schemas\CashMutateForm;
use App\Filament\Resources\CashMutates\Tables\CashMutatesTable;
use App\Models\CashMutate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

use Illuminate\Support\Facades\Auth;
use UnitEnum;

class CashMutateResource extends Resource
{
    protected static ?string $model = CashMutate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'Mutasi Kas';
    protected static ?int $navigationSort = 4;
    protected static ?string $navigationLabel = 'Mutasi Kas';
    protected static string | UnitEnum | null $navigationGroup = 'Mutasi & Kas';

    public static function form(Schema $schema): Schema
    {
        return CashMutateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CashMutatesTable::configure($table);
    }
    public static function canAccess(): bool
    {
        return canAny([
            "Mutasi Kas",
            "Konfirmasi Mutasi Uang",
            "Coordinator Resources"
        ]);
        // return can("Mutasi Kas") || can("Konfirmasi Mutasi Uang")
        //     || can("Coordinator Resources");
    }
    public static function getEloquentQuery(): Builder
    {
        $dealerCodes = Auth::user()->dealer_users->pluck('dealers.dealer_code')->toArray();


        $startDate = now()->startOfMonth();
        $endDate   = now()->endOfMonth();

        return parent::getEloquentQuery()
            ->with(['users'])
            ->whereIn('dealer_code', $dealerCodes)
            ->latest();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCashMutates::route('/'),
            'create' => CreateCashMutate::route('/create'),
            'edit' => EditCashMutate::route('/{record}/edit'),
            'detail' => CashMutateDetail::route('/{record}'),
        ];
    }
}
