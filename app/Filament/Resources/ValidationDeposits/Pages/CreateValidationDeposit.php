<?php

namespace App\Filament\Resources\ValidationDeposits\Pages;

use App\Filament\Resources\ValidationDeposits\ValidationDepositResource;
use App\Models\ApprovalValidation;
use App\Models\ValidateImage;
use App\Models\ValidationDeposit;
use Carbon\Carbon;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateValidationDeposit extends CreateRecord
{
    protected static string $resource = ValidationDepositResource::class;

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
            'customer_name' => $data['customer_name'],
            'nominal_deposit' => $data['nominal_deposit'],
            'date_published' => $data['date_published'],
            'bank_name' => $data['bank_name'],
            'dealer_code' => $data['dealer_code'],
            'transaction_type' => $data['transaction_type'],
            'status' => isCoordinator() ? "approve" : 'request',
            'deadline_time' => Carbon::now()->addDay()->setHour(14)->setMinute(0)->setSecond(0),
            'user_id' => Auth::user()->id,
            'description' => $data['description'],
            'neq_name' => $data['neq_name'],
            'deposit_date' => $data['deposit_date'],
        ]);
        if (isset($data['validate_images']) && count($data['validate_images']) > 0) {
            foreach ($data['validate_images'] as $image) {
                ValidateImage::create([
                    'image' => $image,
                    'validate_deposits_id' => $validationDeposit->id,
                ]);
            }
        }
        $approval_validation = new ApprovalValidation();
        $approval_validation->validation_deposits_id = $validationDeposit->id;
        $approval_validation->description = isCoordinator() ? "Coordinator Melaporkan Validasi Setoran" : "Fin Ops Melaporkan Validasi Setoran Kepada Finance Spv";
        $approval_validation->user_id = Auth::user()->id;
        $approval_validation->status = isCoordinator() ? "approve" : "request";
        $approval_validation->save();

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
