<?php

namespace App\Filament\Resources\CashierDeposits\Pages;

use App\Filament\Resources\CashierDeposits\CashierDepositResource;
use App\Models\ApprovalCashierDeposit;
use App\Models\CashierDeposit;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EditCashierDeposit extends EditRecord
{
    protected static string $resource = CashierDepositResource::class;
    public function getBreadcrumbs(): array
    {
        return [
            'Daftar Setoran Harian Brankas',
            'Detail Setoran Brankas',
            'Edit Setoran',
        ];
    }
    protected static ?string $title = "Edit Setoran Harian Brankas";
    protected function resolveRecord($key): CashierDeposit
    {
        $data = CashierDeposit::with(['dealers', 'cashier_images'])->findOrFail($key); // pastikan relasi pakai connection masing-masing

        return $data;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $cashier = CashierDeposit::with(['dealers', 'cashier_images', 'users'])->findOrFail($data['id']); // pastikan relasi pakai connection masing-masing


        $data = [
            ...$data,
            'name' => $cashier->users->name,
            'today_income' => $cashier->today_income,
            'start_balance' => $cashier->start_balance,
            'cashier_images' => $cashier->cashier_images->map(function ($file) {
                return "/upload/deposit_box/" . $file->image;
            })->toArray(),
        ];
        // dd($data);
        return $data;
    }
    protected function getSavedNotification(): ?\Filament\Notifications\Notification
    {
        return null; // hilangkan toast default
    }

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): Model
    {
        // dd($data);
        $user_id = Auth::user()->id;
        CashierDeposit::find($record['id'])
            ->update([
                'date_published' => $data['date_published'],
                'dealer_code' => $data['dealer_code'],
                'user_id' => Auth::user()->id,
                'expense' => $data['expense'],
                'bank_deposit' => $data['bank_deposit'],
                'invoice' => $data['invoice_nominal'],
                'start_balance' => $data['start_balance'],
                'today_income' => $data['today_income'],
                'bank_name' => $data['bank_name'],
                'end_balance' => $data['end_balance'],
                'total_deposit' => $data['total_deposit'],
                'status' => isCoordinator() ? 'approve' : 'request',
                'approval_type' => 'FinOps',
                'description' => $data['description']
            ]);


        $approval_data = new ApprovalCashierDeposit();
        $approval_data->cashier_deposit_id = $record['id'];
        $approval_data->user_id = Auth::user()->id;
        $approval_data->description = isCoordinator() ? "Coordinator Melakukan revisi" : "Kasir Melakukan Revisi Laporan Setoran Harian ke Brankas Dikirimkan Kembali Kepada Finance Ops";
        $approval_data->status = isCoordinator() ? "approve" : "request";
        $approval_data->save();
        // Replace gambar lama
        if (!empty($data['cashier_images'])) {
            // hapus gambar lama
            $record->cashier_images()->delete();

            // simpan gambar baru
            foreach ($data['cashier_images'] as $file) {
                $record->cashier_images()->create([
                    'image' => $file, // path file yang diupload
                ]);
            }
        }
        return $record->fresh();
    }
    public function afterSave(): string
    {

        // Toast sukses
        Notification::make()
            ->title('Setoran Harian Brankas berhasil diperbarui')
            ->success()
            ->send();

        return CashierDepositResource::getUrl('detail', ['record' => $this->record]);
    }
    protected function getHeaderActions(): array
    {
        return [
            // DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('detail', ['record' => $this->record]);
    }
}
