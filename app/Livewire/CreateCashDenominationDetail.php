<?php

namespace App\Livewire;

use App\Models\CashMutate;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class CreateCashDenominationDetail extends TableWidget
{
    public function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])

            ->headerActions([
                //
            ])
            ->recordActions([
                //
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }
}
