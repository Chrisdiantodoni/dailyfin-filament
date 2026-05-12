<?php

namespace App\Filament\Resources\ValidationDeposits;

use App\Filament\Pages\ValidationDepositDetail;
use App\Filament\Resources\ValidationDeposits\Pages\CreateValidationDeposit;
use App\Filament\Resources\ValidationDeposits\Pages\EditValidationDeposit;
use App\Filament\Resources\ValidationDeposits\Pages\ListValidationDeposits;
use App\Filament\Resources\ValidationDeposits\Schemas\ValidationDepositForm;
use App\Filament\Resources\ValidationDeposits\Tables\ValidationDepositsTable;
use App\Models\ValidationDeposit;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class ValidationDepositResource extends Resource
{
    protected static ?string $model = ValidationDeposit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::MagnifyingGlassCircle;

    protected static ?string $recordTitleAttribute = 'Validasi Setoran';

    protected static string | UnitEnum | null $navigationGroup = 'Setoran & Validasi';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return ValidationDepositForm::configure($schema);
    }

    public static function canAccess(): bool
    {
        return canAny([
            "Validasi Setoran",
            "Konfirmasi Validasi Setoran",
            "Coordinator Resources",
        ]);
        // return can("Validasi Setoran")
        //     || can("Konfirmasi Validasi Setoran")
        //     || can("Coordinator Resources");
    }

    public static function table(Table $table): Table
    {
        return ValidationDepositsTable::configure($table);
    }
    public static function getEloquentQuery(): Builder
    {
        $dealerCodes = Auth::user()->dealer_users->pluck('dealers.dealer_code')->toArray();

        return parent::getEloquentQuery()
            ->whereIn('dealer_code', $dealerCodes)
            ->latest();
    }
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListValidationDeposits::route('/'),
            'create' => CreateValidationDeposit::route('/create'),
            'edit' => EditValidationDeposit::route('/{record}/edit'),
            'detail' => ValidationDepositDetail::route('/{record}'),
        ];
    }
}
