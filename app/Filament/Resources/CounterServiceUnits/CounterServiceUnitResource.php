<?php

namespace App\Filament\Resources\CounterServiceUnits;

use App\Filament\Pages\CsUnitDetail;
use App\Filament\Resources\CounterServiceUnits\Pages\CreateCounterServiceUnit;
use App\Filament\Resources\CounterServiceUnits\Pages\EditCounterServiceUnit;
use App\Filament\Resources\CounterServiceUnits\Pages\ListCounterServiceUnits;
use App\Filament\Resources\CounterServiceUnits\Schemas\CounterServiceUnitForm;
use App\Filament\Resources\CounterServiceUnits\Tables\CounterServiceUnitsTable;
use App\Models\CounterServiceUnit;
use App\Models\CsUnit;
use BackedEnum;
use Filament\Navigation\NavigationItem;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class CounterServiceUnitResource extends Resource
{
    protected static ?string $model = CsUnit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static ?string $navigationLabel = 'Setoran Unit';
    protected static string | UnitEnum | null $navigationGroup = 'Setoran ke Kasir';
    protected static ?string $recordTitleAttribute = 'CsUnit';

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function canAccess(): bool
    {
        return canAny([
            "Setoran Ke Kasir",
            "Konfirmasi Permintaan Setoran Unit",
            "Report Setoran Kasir",
            "Report Setoran Unit Counter Service ke Kasir",
            "Coordinator Resources"
        ]);
        // return can("Setoran Ke Kasir")
        //     || can("Konfirmasi Permintaan Setoran Unit")
        //     || can("Report Setoran Kasir")
        //     || can("Report Setoran Unit Counter Service ke Kasir")
        //     || can("Coordinator Resources");
    }

    public static function form(Schema $schema): Schema
    {
        return CounterServiceUnitForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CounterServiceUnitsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $dealerCodes = Auth::user()->dealer_users()->pluck('dealer_code')->all();

        return parent::getEloquentQuery()
            ->with(['users', 'unit_nominal_dtls', 'dealers', 'unit_images', 'approval_cs_cashiers_units.user'])
            ->whereIn('dealer_code', $dealerCodes)
            ->latest();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCounterServiceUnits::route('/'),
            'create' => CreateCounterServiceUnit::route('/create'),
            'edit' => EditCounterServiceUnit::route('/{record}/edit'),
            'detail' => CsUnitDetail::route('/{record}')
        ];
    }
}
