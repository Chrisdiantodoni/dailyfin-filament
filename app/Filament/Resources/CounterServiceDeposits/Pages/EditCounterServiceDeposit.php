<?php

namespace App\Filament\Resources\CounterServiceDeposits\Pages;

use App\Filament\Resources\CounterServiceDeposits\CounterServiceDepositResource;
use App\Filament\Resources\CounterServiceDeposits\Schemas\CounterServiceDepositForm;
use App\Models\ApprovalCsCashier;
use App\Models\CsServiceSparepart;
use App\Models\ServiceNominalDtl;
use App\Services\ImageCompressionService;
use App\Support\UploadStorage;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EditCounterServiceDeposit extends EditRecord
{


    protected $imageService;

    public function boot(ImageCompressionService $imageService)
    {
        $this->imageService = $imageService;
    }
    public function getBreadcrumbs(): array
    {
        return [
            'Daftar Setoran Sparepart dan Jasa',
            'Setoran',
            'Edit Setoran',
        ];
    }

    protected static ?string $title = "Edit Setoran Counter Service";
    protected static string $resource = CounterServiceDepositResource::class;

    protected function resolveRecord($key): CsServiceSparepart
    {
        $data = CsServiceSparepart::with(['service_nominal_dtls', 'dealers', 'service_images'])->findOrFail($key); // pastikan relasi pakai connection masing-masing

        return $data;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $cs_service_sparepart = CsServiceSparepart::with(['users', 'service_nominal_dtls', 'dealers', 'service_images'])->findOrFail($data['id']); // pastikan relasi pakai connection masing-masing

        $data = [
            ...$data,
            'name' => $cs_service_sparepart->users->name,
            'role' => Auth::user()->roles->first()->name,
            'service_nominal_dtls' => [
                'cash' => $cs_service_sparepart->service_nominal_dtls->cash,
                'transfer' => $cs_service_sparepart->service_nominal_dtls->transfer,
            ],
            'total_expense' => $cs_service_sparepart->total_expense,
            'total_income' => $cs_service_sparepart->total_income,
            'dealer_code' => $cs_service_sparepart->dealer_code,
            'service_images_upload' => $cs_service_sparepart->service_images->map(function ($file) {
                return "/upload/sparepart_deposit/" . $file->image;
            })->toArray(),
        ];
        // dd($data);
        return $data;
    }

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): Model
    {
        // dd($data);
        $user_id = Auth::user()->id;
        CsServiceSparepart::find($record['id'])
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
        $service_nominal_dtl = ServiceNominalDtl::find($record->service_sparepart_dtl);
        ServiceNominalDtl::find($service_nominal_dtl->id)->update([
            'cash' => $data["service_nominal_dtls"]['cash'] ?? 0,
            'transfer' => $data["service_nominal_dtls"]['transfer'] ?? 0
        ]);

        $approval_data = new ApprovalCsCashier();
        $approval_data->cs_service_spareparts_id = $record['id'];
        $approval_data->user_id = Auth::user()->id;
        $approval_data->description = 'Counter Melakukan Revisi dan Mengajukan Kembali Kepada Kasir';
        $approval_data->status = 'request';
        $approval_data->save();
        // --- LOGIKA IMAGE SYNC ---

        $formImages = $data['service_images_upload'] ?? [];

        // --- A. HAPUS GAMBAR LAMA YANG DIBUANG USER ---
        // Kita ambil semua gambar di DB, lalu cek apakah path lengkapnya masih ada di form
        $record->service_images->each(function ($oldImage) use ($formImages) {
            $fullPathInForm = '/upload/sparepart_deposit/' . $oldImage->image;

            if (!in_array($fullPathInForm, $formImages)) {
                // Hapus fisik & record jika sudah tidak ada di form
                UploadStorage::deleteFinal('upload/sparepart_deposit/' . $oldImage->image);
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
                'upload/sparepart_deposit',
            );

            if (! $filename) {
                continue;
            }

            $record->service_images()->create([
                'image' => $filename,
            ]);
        }
        // // Replace gambar lama
        // if (!empty($data['service_images_upload'])) {
        //     // hapus gambar lama
        //     $record->service_images()->delete();

        //     // simpan gambar baru
        //     foreach ($data['service_images_upload'] as $file) {
        //         $record->service_images()->create([
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
    public function afterSave(): ?string
    {

        // Toast sukses
        Notification::make()
            ->title('Setoran berhasil diperbarui')
            ->success()
            ->send();
        return $this->getResource()::getUrl('detail', ['record' => $this->record]);
        // return CounterServiceDepositResource::getUrl('detail', ['record' => $this->record]);
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('detail', ['record' => $this->record]);
    }
    protected function getHeaderActions(): array
    {
        return [
            // DeleteAction::make(),
        ];
    }
}
