<?php

namespace App\Filament\Resources\CashierDeposits\Pages;

use App\Filament\Resources\CashierDeposits\CashierDepositResource;
use App\Models\ApprovalCashierDeposit;
use App\Models\CashierDeposit;
use App\Services\ImageCompressionService;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EditCashierDeposit extends EditRecord
{
    protected static string $resource = CashierDepositResource::class;

    protected $imageService;

    public function boot(ImageCompressionService $imageService)
    {
        $this->imageService = $imageService;
    }
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

        $formImages = $data['cashier_images'] ?? [];

        // --- A. HAPUS GAMBAR LAMA YANG DIBUANG USER ---
        // Kita ambil semua gambar di DB, lalu cek apakah path lengkapnya masih ada di form
        $record->cashier_images->each(function ($oldImage) use ($formImages) {
            $fullPathInForm = '/upload/deposit_box/' . $oldImage->image;

            if (!in_array($fullPathInForm, $formImages)) {
                // Hapus fisik & record jika sudah tidak ada di form
                Storage::disk('public')->delete('upload/deposit_box/' . $oldImage->image);
                $oldImage->delete();
            }
        });

        // --- B. PROSES GAMBAR BARU ---
        foreach ($formImages as $fileInput) {
            // Cek: Jika TIDAK diawali '/upload/', berarti ini file baru yang butuh diproses
            if (!str_starts_with($fileInput, '/upload/')) {

                // Asumsi: fileInput di sini adalah path dari livewire-tmp
                if (!Storage::disk('public')->exists($fileInput)) {
                    continue;
                }

                $absolutePath = Storage::disk('public')->path($fileInput);

                $uploadedFile = new UploadedFile(
                    $absolutePath,
                    basename($absolutePath),
                    mime_content_type($absolutePath),
                    null,
                    true
                );

                // Generate ULID & Konversi ke WebP
                $filename = Str::ulid()->toBase32() . '.webp';
                $compressed = $this->imageService->convertToWebP($uploadedFile);

                $targetDir = 'upload/deposit_box';
                Storage::disk('public')->put($targetDir . '/' . $filename, (string) $compressed);

                // Simpan ke DB
                $record->cashier_images()->create([
                    'image' => $filename,
                ]);

                // Hapus file temporary
                Storage::disk('public')->delete($fileInput);
            }
        }
        // if (!empty($data['cashier_images'])) {
        //     // hapus gambar lama
        //     $record->cashier_images()->delete();

        //     // simpan gambar baru
        //     foreach ($data['cashier_images'] as $file) {
        //         $record->cashier_images()->create([
        //             'image' => $file, // path file yang diupload
        //         ]);
        //     }
        // }
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
