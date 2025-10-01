<?php

namespace App\Filament\Resources\CounterServiceUnits\Pages;

use App\Filament\Resources\CounterServiceUnits\CounterServiceUnitResource;
use App\Models\ApprovalCsCashiersUnit;
use App\Models\CsUnit;
use App\Models\UnitImage;
use App\Models\UnitNominalDtl;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateCounterServiceUnit extends CreateRecord
{
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

        foreach ($data['unit_images_upload'] as $filePath) {
            UnitImage::create([
                'image' => $filePath,
                'cs_units_id' => $cs_unit->id,
            ]);
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
