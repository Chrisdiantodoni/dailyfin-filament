<?php

namespace App\Filament\Resources\CashMutates\Pages;

use App\Filament\Resources\CashMutates\CashMutateResource;
use App\Models\ApprovalMutateCash;
use App\Models\CashImages;
use App\Models\CashMutate;
use App\Services\ImageCompressionService;
use App\Support\UploadStorage;
use App\Support\UserDealerContext;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CreateCashMutate extends CreateRecord
{
    protected static string $resource = CashMutateResource::class;

    protected $imageService;

    public function boot(ImageCompressionService $imageService)
    {
        $this->imageService = $imageService;
    }

    public function getBreadcrumbs(): array
    {
        return [
            'Daftar Mutasi Kas',
            'Mutasi Kas',
        ];
    }

    protected static bool $canCreateAnother = false;
    protected static ?string $title = "Mutasi Kas";
    public $denomination = [
        'denom_100k',
        'total' => 0,
    ];

    public function beforeCreate(): void
    {

        $data = $this->data;

        $dealer_code = UserDealerContext::resolveDealerCode($data);
        $lastSubmission = CashMutate::where('dealer_code', $dealer_code)->latest()->first();
        if ($lastSubmission) {
            $date_published = Carbon::parse($lastSubmission->date_published);
            if ($date_published->isToday()) {
                Notification::make()
                    ->title('Gagal Menyimpan')
                    ->body('Mutasi kas sudah tersubmit sebelumnya')
                    ->danger()
                    ->send();
                throw new Halt();
            }
        }
        $physical_cash   = (int) str_replace('.', '', $data['physical_cash'] ?? 0);
        $total_cash = (int) str_replace('.', '', $data['total_cash'] ?? 0);

        if ($physical_cash < 0 || $total_cash < 0) {
            Notification::make()
                ->title('Gagal Menyimpan')
                ->body('Saldo Akhir dan Total Setoran Brankas tidak boleh dibawah 0.')
                ->danger()
                ->send();
            throw new Halt();
        } elseif ($physical_cash != $total_cash) {
            Notification::make()
                ->title('Gagal Menyimpan')
                ->body('Fisik Kas dan Total Kas harus sama.')
                ->danger()
                ->send();
            throw new Halt();
        } else if ($data['cash_images'] == null) {
            Notification::make()
                ->title('Gagal Menyimpan')
                ->body('Foto Fisik Kas harus diisi.')
                ->danger()
                ->send();
            return;
        }
    }
    protected function handleRecordCreation(array $data): Model
    {
        $cash_mutate = CashMutate::create([
            'start_balance' => $data['start_balance'] ?? 0,
            'end_balance' => $data['end_balance'] ?? 0,
            'cash_difference' => $data['start_balance'] ?? 0 - $data['end_balance'] ?? 0,
            'invoice_nominal' => $data['invoice_nominal'] ?? 0,
            'physical_cash' => $data['physical_cash'] ?? 0,
            'date_published' => $data['date_published'],
            'deadline_time' => Carbon::now()->addDay()->setHour(14)->setMinute(0)->setSecond(0),

            'approval_type' => 'finSpv',
            'status' => 'request',
            'dealer_code' => UserDealerContext::resolveDealerCode($data),
            'description' => $data['description'] ?? "",
            'income' =>
            $data['income'] ?? 0,
            'expense' =>
            $data['expense'] ?? 0,
            'denom_100k' =>
            $data['denom_100k'],
            'denom_75k' =>
            $data['denom_75k'],
            'denom_50k' =>
            $data['denom_50k'],
            'denom_20k' =>
            $data['denom_20k'],
            'denom_10k' =>
            $data['denom_10k'],
            'denom_5k' =>
            $data['denom_5k'],
            'denom_2k' =>
            $data['denom_2k'],
            'denom_1k' =>
            $data['denom_1k'],
            'denom_500' =>
            $data['denom_500'],
            'denom_200' =>
            $data['denom_200'],
            'denom_100' =>
            $data['denom_100'],
            'total_physical_cash' =>
            $data['total_cash'],
            'description2' => $data['description2'],
        ]);

        $approval_data = new ApprovalMutateCash();
        $approval_data->user_id = Auth::user()->id;
        $approval_data->description = isCoordinator() ? "Coordinator Melaporkan Laporan Mutasi Kas" : "Finance Ops Melaporkan Laporan Mutasi Kas dikirim ke Finance Spv";
        $approval_data->status = "request";
        $approval_data->cash_mutates_id = $cash_mutate->id;
        $approval_data->save();


        foreach ($data['cash_images'] ?? [] as $filePath) {
            $filename = UploadStorage::storeCompressedWebp(
                $this->imageService,
                $filePath,
                'upload/cash_mutates',
            );

            if (! $filename) {
                continue;
            }

            CashImages::create([
                'image' => $filename,
                'cash_mutates_id' => $cash_mutate->id,
            ]);
        }
        // foreach ($data['cash_images'] as $filePath) {
        //     CashImages::create([
        //         'image' => $filePath,
        //         'cash_mutates_id' => $cash_mutate->id,

        //     ]);
        // }
        return $cash_mutate;
    }
    protected function getRedirectUrl(): string
    {
        return CashMutateResource::getUrl();
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Mutasi Kas Berhasil Dibuat';
    }
}
