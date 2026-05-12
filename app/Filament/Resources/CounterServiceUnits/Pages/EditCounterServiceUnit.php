<?php

namespace App\Filament\Resources\CounterServiceUnits\Pages;

use App\Filament\Resources\CounterServiceUnits\CounterServiceUnitResource;
use App\Models\ApprovalCsCashiersUnit;
use App\Models\CsUnit;
use App\Models\UnitNominalDtl;
use App\Services\ImageCompressionService;
use App\Support\UploadStorage;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EditCounterServiceUnit extends EditRecord
{
    protected static string $resource = CounterServiceUnitResource::class;

    protected $imageService;

    public function boot(ImageCompressionService $imageService)
    {
        $this->imageService = $imageService;
    }
    public function getBreadcrumbs(): array
    {
        return [
            'Daftar Setoran Unit',
            'Setoran',
            'Edit Setoran',
        ];
    }

    protected static ?string $title = "Edit Setoran Counter Service";



    protected function resolveRecord($key): CsUnit
    {
        $data = CsUnit::with(['unit_nominal_dtls', 'dealers', 'unit_images'])->findOrFail($key); // pastikan relasi pakai connection masing-masing

        return $data;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $cs_service_sparepart = CsUnit::with(['users', 'unit_nominal_dtls', 'dealers', 'unit_images'])->findOrFail($data['id']); // pastikan relasi pakai connection masing-masing

        $data = [
            ...$data,
            'name' => $cs_service_sparepart->users->name,
            'role' => Auth::user()->roles->first()->name,
            'unit_nominal_dtl' => [
                'cash' => $cs_service_sparepart->unit_nominal_dtls->cash,
                'transfer' => $cs_service_sparepart->unit_nominal_dtls->transfer,
            ],
            'total_expense' => $cs_service_sparepart->total_expense,
            'total_income' => $cs_service_sparepart->total_income,
            'dealer_code' => $cs_service_sparepart->dealer_code,
            'unit_images_upload' => $cs_service_sparepart->unit_images->map(function ($file) {
                return "/upload/unit_deposit/" . $file->image;
            })->toArray(),
        ];
        // dd($data);
        return $data;
    }

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): Model
    {
        // dd($data);
        $user_id = Auth::user()->id;
        CsUnit::find($record['id'])
            ->update([
                'date_published' => $data['date_published'],
                'dealer_code' => $data['dealer_code'] ?? 0,
                'total_income' => $data['total_income'] ?? 0,
                'total_expense' => $data['total_expense'] ?? 0,
                'user_id' => $user_id,
                'approval_type' => 'Cashier',
                'description' => $data['description'],
                'status' => 'request',
            ]);
        $unit_nominal_dtl = UnitNominalDtl::find($record->unit_nominal_dtl_id);
        UnitNominalDtl::find($unit_nominal_dtl->id)->update([
            'cash' => $data["unit_nominal_dtl"]['cash'] ?? 0,
            'transfer' => $data["unit_nominal_dtl"]['transfer'] ?? 0
        ]);

        $approval_data = new ApprovalCsCashiersUnit();
        $approval_data->cs_units_id = $record['id'];
        $approval_data->user_id = Auth::user()->id;
        $approval_data->description = 'Counter Melakukan Revisi dan Mengajukan Kembali Kepada Kasir';
        $approval_data->status = 'request';
        $approval_data->save();

        $formImages = $data['unit_images_upload'] ?? [];

        // --- A. HAPUS GAMBAR LAMA YANG DIBUANG USER ---
        // Kita ambil semua gambar di DB, lalu cek apakah path lengkapnya masih ada di form
        $record->unit_images->each(function ($oldImage) use ($formImages) {
            $fullPathInForm = '/upload/unit_deposit/' . $oldImage->image;

            if (!in_array($fullPathInForm, $formImages)) {
                // Hapus fisik & record jika sudah tidak ada di form
                UploadStorage::deleteFinal('upload/unit_deposit/' . $oldImage->image);
                $oldImage->delete();
            }
        });

        // --- B. PROSES GAMBAR BARU ---
        foreach ($formImages as $fileInput) {
            if (UploadStorage::isExistingReference($fileInput)) {
                continue;
            }

            $filename = UploadStorage::storeCompressedWebp(
                $this->imageService,
                $fileInput,
                'upload/unit_deposit',
            );

            if (! $filename) {
                continue;
            }

            $record->unit_images()->create([
                'image' => $filename,
            ]);
        }
        // Replace gambar lama
        // if (!empty($data['unit_images_upload'])) {
        //     // hapus gambar lama
        //     $record->unit_images()->delete();

        //     // simpan gambar baru
        //     foreach ($data['unit_images_upload'] as $file) {
        //         $record->unit_images()->create([
        //             'image' => $file, // path file yang diupload
        //         ]);
        //     }
        // }
        return $record->fresh();
    }
    protected function getSavedNotification(): ?\Filament\Notifications\Notification
    {
        return null; // hilangkan toast default
    }
    public function afterSave(): string
    {

        // Toast sukses
        Notification::make()
            ->title('Setoran berhasil diperbarui')
            ->success()
            ->send();
        return CounterServiceUnitResource::getUrl('detail', ['record' => $this->record]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('detail', ['record' => $this->record]);
    }
}
