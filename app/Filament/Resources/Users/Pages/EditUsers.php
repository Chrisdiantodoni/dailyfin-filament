<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UsersResource;
use App\Models\DealerUser;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission;

class EditUsers extends EditRecord
{
    protected static string $resource = UsersResource::class;

    public function getBreadcrumbs(): array
    {
        return [
            'Daftar User',
            'Edit User',
        ];
    }

    protected function getFormActions(): array
    {
        return []; // kosong → tidak ada tombol Save/Cancel
    }

    protected static ?string $title = "Edit User";
    protected function resolveRecord($key): Model
    {
        // Cari sebagai integer dulu (untuk administrator ID: 1)
        $record = User::with(['dealer_users.dealers'])->where('id', $key)->first();

        if (!$record) {
            // Cari sebagai string (untuk UUID)
            $record = User::with(['dealer_users.dealers'])->where('id', (string) $key)->first();
        }

        if (!$record) {
            abort(404);
        }
        // dd($record);

        return $record;
    }


    protected function getHeaderActions(): array
    {
        return [
            // DeleteAction::make(),
        ];
    }
}
