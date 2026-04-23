<?php

namespace App\Filament\Resources\TakeoutMoney\Pages;

use App\Filament\Resources\TakeoutMoney\TakeoutMoneyResource;
use App\Models\approval_takeout_money;
use App\Models\cashier_takeout_money;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateTakeoutMoney extends CreateRecord
{

    protected static string $resource = TakeoutMoneyResource::class;

    public function getBreadcrumbs(): array
    {
        return [
            'Daftar Keluar Uang Brankas',
            'Keluar Uang Brankas',
        ];
    }

    protected static bool $canCreateAnother = false;

    protected static ?string $title = "Keluar Uang Brankas";

    public function beforeCreate(): void
    {
        $data = $this->data;
        $dealer_code = $data['dealer_code'];
        $lastSubmission = cashier_takeout_money::where('dealer_code', $dealer_code)->latest()->first();
        if ($lastSubmission) {
            $date_published = Carbon::parse($lastSubmission->date_published);
            if ($date_published->isToday()) {
                Notification::make()
                    ->title('Gagal Menyimpan')
                    ->body('Keluar uang brankas sudah tersubmit sebelumnya')
                    ->danger()
                    ->send();
                throw new Halt();
            }
        }
    }


    protected function handleRecordCreation(array $data): Model
    {
        $cashier_takeout = new cashier_takeout_money();
        $cashier_takeout->dealer_code = $data['dealer_code'];
        $cashier_takeout->date_published = $data['date_published'];
        $cashier_takeout->user_id = Auth::user()->id;
        $cashier_takeout->takeout_nominal = $data['takeout_nominal'] ?? 0;
        $cashier_takeout->end_balance = $data['end_balance'] ?? 0;
        $cashier_takeout->money_put = $data['money_put'] ?? 0;
        $cashier_takeout->is_revised = false;
        $cashier_takeout->status = isCoordinator() ? "approve" : "request";
        $cashier_takeout->description = $data['description'];
        $cashier_takeout->approval_type = 'FinOps';
        $cashier_takeout->save();
        $approval_takeout_money = new approval_takeout_money();
        $approval_takeout_money->user_id = Auth::user()->id;
        $approval_takeout_money->description = isCoordinator() ? "Administrator melaporkan Pengeluaran Uang" : "Kasir melaporkan Pengeluaran uang kepada Finance Ops";
        $approval_takeout_money->status = isCoordinator() ? "approve" : "request";
        $approval_takeout_money->cashier_takeouts_id = $cashier_takeout->id;
        $approval_takeout_money->save();

        return $cashier_takeout;
    }
    protected function getRedirectUrl(): string
    {
        return TakeoutMoneyResource::getUrl();
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Laporan Keluar Uang Brankas Berhasil Dibuat';
    }
}
