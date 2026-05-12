<?php

namespace App\Filament\Resources\Dealers\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DealerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Input Dealer')
                    ->schema([
                        TextInput::make('dealer_code')->label("Kode Dealer")
                            ->unique(ignoreRecord: true)
                            ->required(),
                        TextInput::make('dealer_name')->label("Nama Dealer")
                            ->required(),
                        Select::make("area")->required()->options([
                            "NAD" => "NAD",
                            "SUMUT" => "SUMUT",
                            "RIAU" => "RIAU",
                        ]),
                    ])
                    ->columns([
                        'default' => 1,
                        'sm' => 1,
                        'md' => 2,
                        'lg' => 3,
                    ])
                    ->columnSpanFull(),
            ])->columns([
                'default' => 1,
                'sm' => 1,
                'md' => 2,
                'lg' => 3,
            ]);
    }
}
