<?php

namespace App\Filament\Resources\Neqs\Pages;

use App\Filament\Resources\Neqs\NeqResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNeqs extends ListRecords
{
    protected static string $resource = NeqResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
