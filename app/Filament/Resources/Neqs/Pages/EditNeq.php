<?php

namespace App\Filament\Resources\Neqs\Pages;

use App\Filament\Resources\Neqs\NeqResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditNeq extends EditRecord
{
    protected static string $resource = NeqResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): Model
    {
        $record->update([
            'neq_name' => $data['neq_name'],
            'dealer_code' => $data['dealers']
        ]);
        return $record->fresh();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
