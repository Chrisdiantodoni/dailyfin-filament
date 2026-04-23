<?php

namespace App\Filament\Resources\CounterServiceUnits\Pages;

use App\Filament\Resources\CounterServiceUnits\CounterServiceUnitResource;
use App\Models\ApprovalCsCashiersUnit;
use App\Models\CsUnit;
use App\Models\UnitImage;
use App\Models\UnitNominalDtl;
use App\Services\ImageCompressionService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CreateCounterServiceUnit extends CreateRecord
{

    protected $imageService;

    public function boot(ImageCompressionService $imageService)
    {
        $this->imageService = $imageService;
    }
    protected static string $resource = CounterServiceUnitResource::class;

    public function getBreadcrumbs(): array
    {
        return [
            'Daftar Setoran Unit',
            'Setoran Baru',
        ];
    }

    protected static bool $canCreateAnother = false;
    protected static ?string $title = "Setoran Baru Counter Service";

    protected function handleRecordCreation(array $data): Model
    {
        // dd($data);
        $user_id = Auth::user()->id;
        $unit_nominal_dtl = UnitNominalDtl::create([
            'cash' => $data["unit_nominal_dtl"]['cash'] ?? 0,
            'transfer' => $data["unit_nominal_dtl"]['transfer'] ?? 0
        ]);
        $cs_unit = CsUnit::create([
            'date_published' => $data['date_published'],
            'dealer_code' => $data['dealer_code'] ?? $data['dealer_code_single'],
            'total_income' => $data['total_income'] ?? 0,
            'total_expense' => $data['total_expense'] ?? 0,
            'user_id' => $user_id,
            'approval_type' => 'Cashier',
            'description' => $data['description'],
            'status' => isCoordinator() ? "approve" : 'request',
            'unit_nominal_dtl_id' => $unit_nominal_dtl->id
        ]);

        $approval_data = new ApprovalCsCashiersUnit();
        $approval_data->cs_units_id = $cs_unit->id;
        $approval_data->user_id = $user_id;
        $approval_data->description = isCoordinator() ? "Administrator Membuat Laporan Counter Service" : "Counter Melaporkan Pendapatan Kepada Kasir";
        $approval_data->status = isCoordinator() ? "approve" : "request";
        $approval_data->save();

        foreach ($data['unit_images_upload'] ?? [] as $filePath) {
            if (!Storage::disk('public')->exists($filePath)) {
                continue;
            }

            $absolutePath = Storage::disk('public')->path($filePath);

            // 2. Bungkus jadi UploadedFile (Mocking untuk Service)
            $uploadedFile = new UploadedFile(
                $absolutePath,
                basename($absolutePath),
                mime_content_type($absolutePath),
                null,
                true
            );

            // 3. Proses Image via Service (Konversi ke WebP)
            $filename   = $this->imageService->makeUniqueFileName($uploadedFile, 'webp');
            $compressed = $this->imageService->convertToWebP($uploadedFile);

            // 4. Pastikan Directory Tujuan Ada
            $targetDir = 'upload/unit_deposit'; // ✅ Relative path untuk Storage disk
            Storage::disk('public')->makeDirectory($targetDir); // ✅ Auto-create folder jika belum ada

            // 5. Simpan file hasil kompresi via Storage (bukan public_path)
            $targetPath = $targetDir . '/' . $filename;
            Storage::disk('public')->put($targetPath, (string) $compressed); // ✅ Tersimpan di storage/app/public/

            // 6. Simpan ke Database
            UnitImage::create([
                'image' => $filename, // ✅ Simpan relative path, bukan hanya filename
                'cs_units_id' =>  $cs_unit->id,
            ]);

            // 7. Hapus file temporary
            Storage::disk('public')->delete($filePath);
        }

        // if (!empty($services_imgs)) {
        //     foreach ($services_imgs as $img) {

        //     }
        // }




        return $cs_unit;
    }

    protected function getRedirectUrl(): string
    {
        return CounterServiceUnitResource::getUrl();
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Setoran Counter Service Berhasil Dibuat';
    }
}
