<?php

namespace App\Filament\Resources\Neqs\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class NeqForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Input NEQ')
                    ->schema([
                        TextInput::make('neq_name')
                            ->label('Nama NEQ')
                            ->required(),
                        Select::make('dealers')
                            ->label('Dealer')
                            ->relationship('dealer_users.dealers', 'dealer_name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->placeholder('Pilih Dealer'),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
