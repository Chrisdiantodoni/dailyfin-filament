<?php

namespace App\Filament\Resources\CashierDeposits\Pages;

use App\Filament\Resources\CashierDeposits\CashierDepositResource;
use App\Models\ApprovalCashierDeposit;
use App\Models\CashierDeposit;
use App\Models\CashierDepositImage;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateCashierDeposit extends CreateRecord
{
    protected static string $resource = CashierDepositResource::class;
    public function getBreadcrumbs(): array
    {
        return [
            'Daftar Setoran Brankas',
            'Setoran Brankas',
        ];
    }

    protected static bool $canCreateAnother = false;
    protected static ?string $title = "Setoran Harian Brankas";
    public function beforeCreate(): void
    {
        $data = $this->data;
        $end_balance   = (int) str_replace('.', '', $data['end_balance'] ?? 0);
        $total_deposit = (int) str_replace('.', '', $data['total_deposit'] ?? 0);

        if ($end_balance < 0 || $total_deposit < 0) {
            Notification::make()
                ->title('Gagal Menyimpan')
                ->body('Saldo Akhir dan Total Setoran Brankas tidak boleh dibawah 0.')
                ->danger()
                ->send();
            throw new Halt();
        }
    }

    protected function handleRecordCreation(array $data): Model
    {
        $cashier_deposit = CashierDeposit::create([
            'date_published' => $data['date_published'],
            'dealer_code' => $data['dealer_code'] ?? $data['dealer_code_single'],
            'user_id' => Auth::user()->id,
            'expense' => $data['expense'] ?? 0,
            'bank_deposit' => $data['bank_deposit'] ?? 0,
            'invoice' => $data['invoice_nominal'] ?? 0,
            'start_balance' => $data['start_balance'] ?? 0,
            'today_income' => $data['today_income'] ?? 0,
            'bank_name' => $data['bank_name'],
            'end_balance' => $data['end_balance'] ?? 0,
            'total_deposit' => $data['total_deposit'] ?? 0,
            'status' => isCoordinator()  ? 'approve' : 'request',
            'approval_type' => 'FinOps',
            'description' => $data['description'],
        ]);

        $approval_cashier = new ApprovalCashierDeposit();
        $approval_cashier->cashier_deposit_id = $cashier_deposit->id;
        $approval_cashier->description = isCoordinator() ? "Coordinator Melaporan Setoran Brankas" : "Kasir Melaporkan Setoran Uang ke Brankas Kepada Finance Ops";
        $approval_cashier->user_id = Auth::user()->id;
        $approval_cashier->status = isCoordinator() ? "approve" : "request";
        $approval_cashier->save();
        foreach ($data['cashier_images'] as $filePath) {
            CashierDepositImage::create([
                'image' => $filePath,
                'cashier_deposit_id' => $cashier_deposit->id,

            ]);
        }
        return $cashier_deposit;
    }

    protected function getRedirectUrl(): string
    {
        return CashierDepositResource::getUrl();
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Setoran Harian Brankas Berhasil Dibuat';
    }
}
