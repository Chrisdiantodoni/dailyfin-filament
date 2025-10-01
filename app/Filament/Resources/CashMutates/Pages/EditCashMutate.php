<?php

namespace App\Filament\Resources\CashMutates\Pages;

use App\Filament\Resources\CashMutates\CashMutateResource;
use App\Models\ApprovalMutateCash;
use App\Models\CashMutate;
use Carbon\Carbon;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EditCashMutate extends EditRecord
{
    protected static string $resource = CashMutateResource::class;

    // protected function getHeaderActions(): array
    // {
    //     return [
    //         DeleteAction::make(),
    //     ];
    // }

    public function getBreadcrumbs(): array
    {
        return [
            'Daftar Mutasi Kas',
            'Mutasi Kas',
            'Edit Mutasi Kas',
        ];
    }
    protected static ?string $title = "Edit Mutasi Kas";

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $cash_mutates = CashMutate::with(['users', 'cash_images'])->findOrFail($data['id']); // pastikan relasi pakai connection masing-masing

        $data = [
            ...$data,
            'description2' => $cash_mutates->description2,

            'cash_images' => $cash_mutates->cash_images->map(function ($file) {
                return "/upload/cash_mutates/" . $file->image;
            })->toArray(),
        ];
        // dd($data);
        return $data;
    }
    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): Model
    {
        $record->update([
            'start_balance' => $data['start_balance'],
            'end_balance' => $data['end_balance'] ?? 0,
            'cash_difference' => $data['start_balance'] ?? 0 - $data['end_balance'] ?? 0,
            'invoice_nominal' => $data['invoice_nominal'] ?? 0,
            'physical_cash' => $data['physical_cash'] ?? 0,
            'date_published' => $data['date_published'],
            'approval_type' => 'finSpv',
            'status' => isCoordinator() ? "approve" : 'request',
            'dealer_code' => $data['dealer_code'],
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
            'description2' => $data['description2'] ?? '',
        ]);

        $approval_data = new ApprovalMutateCash();
        $approval_data->user_id = Auth::user()->id;
        $approval_data->description = isCoordinator() ? "Coordinator Melakukan Revisi" :  "Finance Ops Melakukan Revisi Laporan Mutasi Kas dan dikirim ke Finance Spv";
        $approval_data->status = isCoordinator() ? "approve" : "request";
        $approval_data->cash_mutates_id = $record->id;
        $approval_data->save();

        if (!empty($data['cash_images'])) {
            // hapus gambar lama
            $record->cash_images()->delete();

            // simpan gambar baru
            foreach ($data['cash_images'] as $file) {
                $record->cash_images()->create([
                    'image' => $file, // path file yang diupload
                ]);
            }
        }
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
            ->title('Mutasi Kas berhasil diperbarui')
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
