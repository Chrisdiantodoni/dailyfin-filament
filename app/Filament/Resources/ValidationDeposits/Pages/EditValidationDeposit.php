<?php

namespace App\Filament\Resources\ValidationDeposits\Pages;

use App\Filament\Resources\ValidationDeposits\ValidationDepositResource;
use App\Models\ApprovalValidation;
use App\Models\ValidationDeposit;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EditValidationDeposit extends EditRecord
{
    protected static string $resource = ValidationDepositResource::class;

    public function getBreadcrumbs(): array
    {
        return [
            'Daftar Validasi Setoran',
            'Validasi Setoran',
            'Edit Validasi Setoran'
        ];
    }
    protected static ?string $title = "Edit Validasi Setoran";
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $validate = ValidationDeposit::with(['users', 'validate_imgs'])->findOrFail($data['id']); // pastikan relasi pakai connection masing-masing

        $data = [
            ...$data,
            'description' => $validate->description,
            'validate_images' => $validate->validate_imgs->map(function ($file) {
                return "/upload/validate/" . $file->image;
            })->toArray(),
        ];
        // dd($data);
        return $data;
    }
    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): Model
    {
        $record->update(
            [
                'customer_name' => $data['customer_name'],
                'nominal_deposit' => $data['nominal_deposit'],
                'date_published' => $data['date_published'],
                'bank_name' => $data['bank_name'],
                'dealer_code' => $data['dealer_code'],
                'transaction_type' => $data['transaction_type'],
                'status' => isCoordinator() ? "approve" : 'request',
                'user_id' => Auth::user()->id,
                'description' => $data['description'],
                'neq_name' => $data['neq_name'] ?? "",
                'deposit_date' => $data['deposit_date'],
            ]
        );
        $approval_validation = new ApprovalValidation();
        $approval_validation->validation_deposits_id = $record->id;
        $approval_validation->description = isCoordinator() ? "Coordinator Melakukan revisi Validasi Setoran" : "Fin Ops Melaporkan Validasi Setoran Kepada Finance Spv";
        $approval_validation->user_id = Auth::user()->id;
        $approval_validation->status = isCoordinator() ? "approve" : "request";
        $approval_validation->save();
        if (!empty($data['validate_images'])) {
            // hapus gambar lama
            $record->validate_imgs()->delete();

            // simpan gambar baru
            foreach ($data['validate_images'] as $file) {
                $record->validate_imgs()->create([
                    'image' => $file, // path file yang diupload
                ]);
            }
        }


        return $record;
    }

    protected function getSavedNotification(): ?\Filament\Notifications\Notification
    {
        return null; // hilangkan toast default
    }
    public function afterSave(): ?string
    {

        // Toast sukses
        Notification::make()
            ->title('Validasi Setoran berhasil diperbarui')
            ->success()
            ->send();
        return $this->getResource()::getUrl('detail', ['record' => $this->record]);
        // return CounterServiceDepositResource::getUrl('detail', ['record' => $this->record]);
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('detail', ['record' => $this->record]);
    }
}
