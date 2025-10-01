<?php

namespace App\Filament\Resources\Neqs\Pages;

use App\Filament\Resources\Neqs\NeqResource;
use App\Models\Neq;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateNeq extends CreateRecord
{
    protected static string $resource = NeqResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $neq = Neq::create([
            'neq_name' => $data['neq_name'],
            'dealer_code' => $data['dealers'],
        ]);
        return $neq;
    }

    protected function getRedirectUrl(): string
    {
        return NeqResource::getUrl();
    }
}
