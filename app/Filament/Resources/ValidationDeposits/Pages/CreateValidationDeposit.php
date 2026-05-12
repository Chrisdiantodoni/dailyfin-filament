<?php

namespace App\Filament\Resources\ValidationDeposits\Pages;

use App\Filament\Resources\ValidationDeposits\ValidationDepositResource;
use App\Models\ApprovalValidation;
use App\Models\ValidateImage;
use App\Models\ValidationDeposit;
use App\Services\ImageCompressionService;
use App\Support\UploadStorage;
use App\Support\UserDealerContext;
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
            'dealer_code' => UserDealerContext::resolveDealerCode($data),
            'transaction_type' => $data['transaction_type'],
            'status' => isAuditCoordinator() ? "approve" : 'request',
            'deadline_time' => Carbon::now()->addDay()->setHour(14)->setMinute(0)->setSecond(0),
            'user_id' => Auth::user()->id,
            'description' => $data['description'],
            'neq_name' => $data['neq_name'] ?? "",
            'deposit_date' => $data['deposit_date'],
        ]);

        $approval_validation = new ApprovalValidation();
        $approval_validation->validation_deposits_id = $validationDeposit->id;
        $approval_validation->description = isAuditCoordinator() ? "Coordinator Melaporkan Validasi Setoran" : "Fin Ops Melaporkan Validasi Setoran Kepada Finance Spv";
        $approval_validation->user_id = Auth::user()->id;
        $approval_validation->status = isAuditCoordinator() ? "approve" : "request";
        $approval_validation->save();


        foreach ($data['validate_images'] ?? [] as $filePath) {
            $filename = UploadStorage::storeCompressedWebp(
                $this->imageService,
                $filePath,
                'upload/validate',
            );

            if (! $filename) {
                continue;
            }

            ValidateImage::create([
                'image' => $filename,
                'validate_deposits_id' => $validationDeposit->id,
            ]);
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
