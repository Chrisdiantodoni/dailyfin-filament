<?php

namespace App\Filament\Resources\CashierDeposits;

use App\Filament\Pages\CashierDepositDetail;
use App\Filament\Resources\CashierDeposits\Pages\CreateCashierDeposit;
use App\Filament\Resources\CashierDeposits\Pages\EditCashierDeposit;
use App\Filament\Resources\CashierDeposits\Pages\ListCashierDeposits;
use App\Filament\Resources\CashierDeposits\Schemas\CashierDepositForm;
use App\Filament\Resources\CashierDeposits\Tables\CashierDepositsTable;
use App\Models\CashierDeposit;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class CashierDepositResource extends Resource
{
    protected static ?string $model = CashierDeposit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::InboxArrowDown;

    protected static ?string $recordTitleAttribute = 'CashierDeposit';
    protected static ?string $navigationLabel = 'Setoran Brankas Sore';

    protected static string | UnitEnum | null $navigationGroup = 'Laporan Kas';

    protected static ?int $navigationSort = 1;
    // protected static string | UnitEnum | null $navigationLabel = 'Setoran Harian Brankas';
    public static function canAccess(): bool
    {

        return canAny([
            "Setoran Harian ke Brankas",
            "Konfirmasi Setoran harian ke Brankas Finance Ops",
            "Konfirmasi Setoran Harian ke Brankas Finance Spv",
            "Coordinator Resources"
        ]);
        // return can("Setoran Harian ke Brankas")
        //     || can('Konfirmasi Setoran harian ke Brankas Finance Ops')
        //     || can("Konfirmasi Setoran Harian ke Brankas Finance Spv")
        //     || can("Coordinator Resources");
    }
    public static function getNavigationBadge(): ?string
    {
        if (getRole() === 'Cashier') {
            return null;
        }

        if (getRole() === 'Finance Operation') {
            $dealerCodes = Auth::user()->dealer_users()->pluck('dealer_code')->all();
            /** @var Builder $badgeQuery */
            $badgeQuery = CashierDeposit::query();

            return (string) $badgeQuery
                ->whereIn('dealer_code', $dealerCodes)
                ->where('is_seen_ops', false)
                ->count();
        }

        return null;
    }

    public static function form(Schema $schema): Schema
    {
        return CashierDepositForm::configure($schema);
    }

    public function getTitle(): string
    {
        return 'Cashier Deposit Management';
    }

    public static function getEloquentQuery(): Builder
    {
        $dealerCodes = Auth::user()->dealer_users()->pluck('dealer_code')->all();

        return parent::getEloquentQuery()
            ->with(['users', 'dealers', 'cashier_images'])
            ->whereIn('dealer_code', $dealerCodes)
            ->latest();
    }
    public static function table(Table $table): Table
    {
        return CashierDepositsTable::configure($table);
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
            'index' => ListCashierDeposits::route('/'),
            'create' => CreateCashierDeposit::route('/create'),
            'edit' => EditCashierDeposit::route('/{record}/edit'),
            'detail' => CashierDepositDetail::route('/{record}')
        ];
    }
}
