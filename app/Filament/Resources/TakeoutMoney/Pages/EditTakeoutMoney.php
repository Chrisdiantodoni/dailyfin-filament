<?php

namespace App\Filament\Resources\TakeoutMoney\Pages;

use App\Filament\Resources\TakeoutMoney\TakeoutMoneyResource;
use App\Models\approval_takeout_money;
use App\Models\cashier_takeout_money;
use App\Support\UserDealerContext;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EditTakeoutMoney extends EditRecord
{
    protected static string $resource = TakeoutMoneyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // DeleteAction::make(),
        ];
    }
    protected static ?string $title = "Edit Keluarkan Uang Kasir";
    protected function resolveRecord($key): cashier_takeout_money
    {
        $data = cashier_takeout_money::with(['dealers'])->findOrFail($key); // pastikan relasi pakai connection masing-masing

        return $data;
    }
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $cashier_takeout_money = cashier_takeout_money::with(['users', 'dealers', 'users'])->findOrFail($data['id']); // pastikan relasi pakai connection masing-masing

        $data = [
            ...$data,
            'name' => $cashier_takeout_money->users->name,
            'role' => Auth::user()->roles->first()->name,
            'total_expense' => $cashier_takeout_money->total_expense,
            'total_income' => $cashier_takeout_money->total_income,
            'dealer_code' => $cashier_takeout_money->dealer_code,
        ];
        // dd($data);
        return $data;
    }

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): Model
    {
        $record->update([
            'date_published' => $data['date_published'],
            'dealer_code' => UserDealerContext::resolveDealerCode($data),
            'end_balance' => $data['end_balance'],
            'takeout_nominal' => $data['takeout_nominal'],
            'revised_ops_nominal' => $data['revised_ops_nominal'],
            'money_put' => $data['money_put'],
            'is_revised' => true,
            'approval_type' => 'FinOps',
            'status' => 'request',
        ]);

        $approval_takeout_money = new approval_takeout_money();
        $approval_takeout_money->user_id = Auth::user()->id;
        $approval_takeout_money->description =  isCoordinator() ? 'Administrator melaporkan pengeluaran uang' : "Kasir Mengajukan Kembali Laporan Pengeluaran Uang Kepada Finance Ops";
        $approval_takeout_money->status = "request";
        $approval_takeout_money->cashier_takeouts_id = $record->id;
        $approval_takeout_money->save();
        return $record->fresh();
    }

    protected function getSavedNotification(): ?\Filament\Notifications\Notification
    {
        return null; // hilangkan toast default
    }

    public function afterSave(): ?string
    {

        // Toast sukses
        Notification::make()
            ->title('Keluarkan Uang berhasil diperbarui')
            ->success()
            ->send();
        return $this->getResource()::getUrl('detail', ['record' => $this->record]);
        // return CounterServiceDepositResource::getUrl('detail', ['record' => $this->record]);
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('detail', ['record' => $this->record]);
    }
}
