<?php

namespace App\Filament\Resources\CounterServiceDeposits;

use App\Filament\Resources\CounterServiceDeposits\Pages\CreateCounterServiceDeposit;
use App\Filament\Resources\CounterServiceDeposits\Pages\CsSparepartDetail;
use App\Filament\Resources\CounterServiceDeposits\Pages\EditCounterServiceDeposit;
use App\Filament\Resources\CounterServiceDeposits\Pages\ListCounterServiceDeposits;
use App\Filament\Resources\CounterServiceDeposits\Schemas\CounterServiceDepositForm;
use App\Filament\Resources\CounterServiceDeposits\Tables\CounterServiceDepositsTable;
use App\Models\CounterServiceDeposit;
use App\Models\CsServiceSparepart;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class CounterServiceDepositResource extends Resource
{
    protected static ?string $model = CsServiceSparepart::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'CsServiceSparepart';
    protected static ?string $navigationLabel = 'Setoran Sparepart & Jasa';
    protected static string | UnitEnum | null $navigationGroup = 'Counter Service';
    public static function canAccess(): bool
    {
        return canAny([
            "Setoran Ke Kasir",
            "Konfirmasi Permintaan Setoran Jasa Service",
            "Report Setoran Kasir",
            "Report Setoran Jasa Service Counter Service ke Kasir",
            "Coordinator Resources"
        ]);
        // || can("Konfirmasi Permintaan Setoran Jasa Service")
        // || can("Report Setoran Kasir")
        // || can("Report Setoran Jasa Service Counter Service ke Kasir")
        // || can("Coordinator Resources");
    }
    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function form(Schema $schema): Schema
    {
        return CounterServiceDepositForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CounterServiceDepositsTable::configure($table);
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


        $startDate = now()->startOfMonth();
        $endDate   = now()->endOfMonth();

        return parent::getEloquentQuery()
            ->with(['users', 'service_nominal_dtls', 'dealers', 'service_images', 'approval_cs_cashiers.user'])
            ->whereIn('dealer_code', $dealerCodes)
            ->latest();
    }


    public static function getPages(): array
    {
        return [
            'index' => ListCounterServiceDeposits::route('/'),
            'create' => CreateCounterServiceDeposit::route('/create'),
            'edit' => EditCounterServiceDeposit::route('/{record}/edit'),
            'detail' => \App\Filament\Pages\CsSparepartDetail::route('/{record}'),
        ];
    }
}
