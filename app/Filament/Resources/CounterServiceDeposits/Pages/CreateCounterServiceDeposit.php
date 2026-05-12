<?php

namespace App\Filament\Resources\CounterServiceDeposits\Pages;

use App\Events\NotificationSent;
use App\Filament\Resources\CounterServiceDeposits\CounterServiceDepositResource;
use App\Models\ApprovalCsCashier;
use App\Models\CsServiceSparepart;
use App\Models\ServiceImage;
use App\Models\ServiceNominalDtl;
use App\Models\SparepartImage;
use App\Models\User;
use App\Services\ImageCompressionService;
use App\Support\UploadStorage;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use Livewire\Component as LivewireComponent;

class CreateCounterServiceDeposit extends CreateRecord
{
    protected static string $resource = CounterServiceDepositResource::class;

    protected $imageService;

    public function boot(ImageCompressionService $imageService)
    {
        $this->imageService = $imageService;
    }
    public function getBreadcrumbs(): array
    {
        return [
            'Daftar Setoran Sparepart dan Jasa',
            'Setoran Baru',
        ];
    }

    protected static bool $canCreateAnother = false;
    protected static ?string $title = "Setoran Baru Counter Service";

    protected function handleRecordCreation(array $data): Model
    {
        // dd($data);
        $user_id = Auth::user()->id;
        $service_nominal_dtls = ServiceNominalDtl::create([
            'cash' => $data["service_nominal_dtls"]['cash'] ?? 0,
            'transfer' => $data["service_nominal_dtls"]['transfer'] ?? 0
        ]);
        $cs_sparepart = CsServiceSparepart::create([
            'date_published' => $data['date_published'],
            'dealer_code' => $data['dealer_code'] ?? $data['dealer_code_single'],
            'total_income' => $data['total_income'] ?? 0,
            'total_expense' => $data['total_expense'] ?? 0,
            'user_id' => $user_id,
            'approval_type' => 'Cashier',
            'description' => $data['description'],
            'status' => isCoordinator() ? 'approve' : 'request',
            'service_sparepart_dtl' => $service_nominal_dtls->id
        ]);

        $approval_data = new ApprovalCsCashier();
        $approval_data->cs_service_spareparts_id = $cs_sparepart->id;
        $approval_data->user_id = $user_id;
        $approval_data->description = isCoordinator() ? "Administrator Membuat Laporan Counter Service" :  "Counter Melaporkan Pendapatan Kepada Kasir";
        $approval_data->status = isCoordinator() ? "approve" : "request";
        $approval_data->save();


        foreach ($data['service_images_upload'] ?? [] as $filePath) {
            $filename = UploadStorage::storeCompressedWebp(
                $this->imageService,
                $filePath,
                'upload/sparepart_deposit',
            );

            if (! $filename) {
                continue;
            }

            ServiceImage::create([
                'image' => $filename,
                'cs_service_spareparts_id' => $cs_sparepart->id,
            ]);
        }
        return $cs_sparepart;
    }

    protected function getRedirectUrl(): string
    {
        return CounterServiceDepositResource::getUrl();
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Setoran Counter Service Berhasil Dibuat';
    }
    // protected function getCreateFormAction(): Action
    // {

    // return CreateAction::make();

    //     return Action::make('create')
    //         ->modalHeading('Konfirmasi Pendapatan Counter')
    //         ->modalDescription('
    //     Laporan akan dikirimkan ke bagian Kasir untuk dilakukan pengecekan.
    //     Notifikasi laporan diterima atau ditolak akan segera kamu dapatkan.
    //     Terima kasih.
    // ')
    //         ->label('Create')
    //         ->requiresConfirmation()
    //         ->button()
    //         ->failureNotificationBody('form tidak lengkap')
    //         ->action(function (array $data, Action $action): void {
    //             // Example: Manual validation and action execution
    //             try {
    //                 // Perform your action
    //                 // ...
    //                 $this->create();
    //             } catch (\Exception $e) {
    //                 // Handle any other exceptions
    //                 $action->failureNotificationTitle($e->getMessage())
    //                     ->failure();
    //             }
    //         })
    //         ->extraAttributes([
    //             'id' => 'create-button',
    //             'x-data' => '{ uploading: false }',
    //             'x-on:file-upload-start.window' => 'uploading = true',
    //             'x-on:file-upload-finish.window' => 'uploading = false',
    //             'x-on:file-upload-error.window' => 'uploading = false',
    //             'x-bind:disabled' => 'uploading',
    //         ]);
    // }
    // protected function getFormActions(): array
    // {
    //     return [];
    // }
}
