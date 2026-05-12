<?php

namespace App\Filament\Resources\Neqs;

use App\Filament\Resources\Neqs\Pages\CreateNeq;
use App\Filament\Resources\Neqs\Pages\EditNeq;
use App\Filament\Resources\Neqs\Pages\ListNeqs;
use App\Filament\Resources\Neqs\Schemas\NeqForm;
use App\Filament\Resources\Neqs\Tables\NeqsTable;
use App\Models\Neq;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class NeqResource extends Resource
{
    protected static ?string $model = Neq::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'NEQ';
    protected static ?int $navigationSort = 6;
    protected static string | UnitEnum | null $navigationGroup = 'Master Data';
    public static function canAccess(): bool
    {
        /** @var \App\Models\User&\Spatie\Permission\Traits\HasRoles $user */
        $user = Auth::user();
        return $user->hasPermissionTo("Tambah User");
    }
    public static function form(Schema $schema): Schema
    {
        return NeqForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NeqsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->latest();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNeqs::route('/'),
            'create' => CreateNeq::route('/create'),
            'edit' => EditNeq::route('/{record}/edit'),
        ];
    }
}
