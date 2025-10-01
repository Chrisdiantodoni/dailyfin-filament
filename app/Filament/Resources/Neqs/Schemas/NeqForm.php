<?php

namespace App\Filament\Resources\Neqs\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class NeqForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('neq_name')->label("Nama NEQ")
                    ->required(),
                Select::make('dealers') // ✅ Nama relationship
                    ->label('Dealer')
                    ->relationship('dealer_users.dealers', 'dealer_name') // ✅ Langsung ke relationship dealers
                    ->required()
                    ->searchable()
                    ->preload()
                    ->placeholder('Pilih Dealer'),
            ]);
    }
}
