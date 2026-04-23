<?php

namespace App\Filament\Resources\ValidationDeposits\Pages;

use App\Filament\Resources\ValidationDeposits\ValidationDepositResource;
use App\Models\ApprovalValidation;
use App\Models\ValidateImage;
use App\Models\ValidationDeposit;
use App\Services\ImageCompressionService;
use Carbon\Carbon;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CreateValidationDeposit extends CreateRecord
{
    protected static string $resource = ValidationDepositResource::class;


    protected $imageService;

    public function boot(ImageCompressionService $imageService)
    {
        $this->imageService = $imageService;
    }


    public function getBreadcrumbs(): array
    {
        return [
            'Daftar Validasi Setoran',
            'Validasi Setoran',
        ];
    }

    protected static bool $canCreateAnother = false;

    protected static ?string $title = "Validasi Setoran";


    protected function handleRecordCreation(array $data): Model
    {
        $validationDeposit =  ValidationDeposit::create([
            'customer_name' => $data['customer_name'] ?? "",
            'nominal_deposit' => $data['nominal_deposit'] ?? 0,
            'date_published' => $data['date_published'],
            'bank_name' => $data['bank_name'] ?? "",
            'dealer_code' => $data['dealer_code'] ?? "",
            'transaction_type' => $data['transaction_type'],
            'status' => isCoordinator() ? "approve" : 'request',
            'deadline_time' => Carbon::now()->addDay()->setHour(14)->setMinute(0)->setSecond(0),
            'user_id' => Auth::user()->id,
            'description' => $data['description'],
            'neq_name' => $data['neq_name'] ?? "",
            'deposit_date' => $data['deposit_date'],
        ]);

        $approval_validation = new ApprovalValidation();
        $approval_validation->validation_deposits_id = $validationDeposit->id;
        $approval_validation->description = isCoordinator() ? "Coordinator Melaporkan Validasi Setoran" : "Fin Ops Melaporkan Validasi Setoran Kepada Finance Spv";
        $approval_validation->user_id = Auth::user()->id;
        $approval_validation->status = isCoordinator() ? "approve" : "request";
        $approval_validation->save();


        foreach ($data['validate_images'] ?? [] as $filePath) {
            if (!Storage::disk('public')->exists($filePath)) {
                continue;
            }

            $absolutePath = Storage::disk('public')->path($filePath);

            $uploadedFile = new UploadedFile(
                $absolutePath,
                basename($absolutePath),
                mime_content_type($absolutePath),
                null,
                true
            );

            // 1. Generate Nama File menggunakan ULID
            // Hasilnya: 01H6XCPN8... .webp
            $filename = Str::ulid()->toBase32() . '.webp';

            // 2. Proses Konversi (Jika imageService butuh file, tetap teruskan)
            $compressed = $this->imageService->convertToWebP($uploadedFile);

            // 3. Simpan via Storage Disk 'public'
            $targetDir = 'upload/validate';
            $targetPath = $targetDir . '/' . $filename;

            // Put file ke storage/app/public/upload/sparepart_deposit/
            Storage::disk('public')->put($targetPath, (string) $compressed);

            // 4. Simpan ke Database
            ValidateImage::create([
                'image'                    => $filename,
                'validate_deposits_id' => $validationDeposit->id,
            ]);

            // 5. Hapus file temporary Filament
            Storage::disk('public')->delete($filePath);
        }

        // if (isset($data['validate_images']) && count($data['validate_images']) > 0) {
        //     foreach ($data['validate_images'] as $image) {
        //         ValidateImage::create([
        //             'image' => $image,
        //             'validate_deposits_id' => $validationDeposit->id,
        //         ]);
        //     }
        // }
        return $validationDeposit;
    }
    protected function getRedirectUrl(): string
    {
        return ValidationDepositResource::getUrl();
    }
    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Validasi Setoran Berhasil Dibuat';
    }
}
