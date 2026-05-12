<?php

namespace App\Filament\Resources\CashierDeposits\Pages;

use App\Filament\Resources\CashierDeposits\CashierDepositResource;
use App\Models\ApprovalCashierDeposit;
use App\Models\cashier_takeout_money;
use App\Models\CashierDeposit;
use App\Models\CashierDepositImage;
use App\Services\ImageCompressionService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CreateCashierDeposit extends CreateRecord
{
    protected $imageService;

    public function boot(ImageCompressionService $imageService)
    {
        $this->imageService = $imageService;
    }

    protected static string $resource = CashierDepositResource::class;

    public function getBreadcrumbs(): array
    {
        return [
            'Daftar Setoran Brankas',
            'Setoran Brankas',
        ];
    }

    protected static bool $canCreateAnother = false;

    protected static ?string $title = 'Setoran Harian Brankas';

    public function beforeCreate(): void
    {
        $data = $this->data;
        $dealerCodes = $data['dealer_code'];
        $yesterdayTakeoutMoney = cashier_takeout_money::where('dealer_code', $dealerCodes)
            ->where('status', 'request')
            ->first();
        if ($yesterdayTakeoutMoney) {
            Notification::make()
                ->title('Gagal Menyimpan')
                ->body('Keluar Uang Brankas belum disetujui')
                ->danger()
                ->send();
            throw new Halt;
        }

        $existingSubmission = CashierDeposit::where('dealer_code', $dealerCodes)
            ->where('date_published', Carbon::today()->toDateString()) // Cek apakah sudah ada di hari ini
            ->exists(); // Cukup cek keberadaan data, tidak perlu fetch record penuh

        if ($existingSubmission) {
            Notification::make()
                ->title('Gagal Menyimpan')
                ->body('Setoran Brankas sudah tersubmit sebelumnya')
                ->danger()
                ->send();
            throw new Halt;
        }
        $end_balance = (int) str_replace('.', '', $data['end_balance'] ?? 0);
        $total_deposit = (int) str_replace('.', '', $data['total_deposit'] ?? 0);

        if ($end_balance < 0 || $total_deposit < 0) {
            Notification::make()
                ->title('Gagal Menyimpan')
                ->body('Saldo Akhir dan Total Setoran Brankas tidak boleh dibawah 0.')
                ->danger()
                ->send();
            throw new Halt;
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
            'status' => isCoordinator() ? 'approve' : 'request',
            'approval_type' => 'FinOps',
            'description' => $data['description'],
        ]);

        $approval_cashier = new ApprovalCashierDeposit;
        $approval_cashier->cashier_deposit_id = $cashier_deposit->id;
        $approval_cashier->description = isCoordinator() ? 'Coordinator Melaporan Setoran Brankas' : 'Kasir Melaporkan Setoran Uang ke Brankas Kepada Finance Ops';
        $approval_cashier->user_id = Auth::user()->id;
        $approval_cashier->status = isCoordinator() ? 'approve' : 'request';
        $approval_cashier->save();

        foreach ($data['cashier_images'] ?? [] as $filePath) {
            if (! Storage::disk('public')->exists($filePath)) {
                continue;
            }

            $absolutePath = Storage::disk('public')->path($filePath);

            // 2. Bungkus jadi UploadedFile (Mocking untuk Service)
            $uploadedFile = new UploadedFile(
                $absolutePath,
                basename($absolutePath),
                mime_content_type($absolutePath),
                null,
                true
            );

            // 3. Proses Image via Service (Konversi ke WebP)
            $filename = $this->imageService->makeUniqueFileName($uploadedFile, 'webp');
            $compressed = $this->imageService->convertToWebP($uploadedFile);

            // 4. Pastikan Directory Tujuan Ada
            $targetDir = 'upload/deposit_box'; // ✅ Relative path untuk Storage disk
            Storage::disk('public')->makeDirectory($targetDir); // ✅ Auto-create folder jika belum ada

            // 5. Simpan file hasil kompresi via Storage (bukan public_path)
            $targetPath = $targetDir.'/'.$filename;
            Storage::disk('public')->put($targetPath, (string) $compressed); // ✅ Tersimpan di storage/app/public/

            // 6. Simpan ke Database
            CashierDepositImage::create([
                'image' => $filename, // ✅ Simpan relative path, bukan hanya filename
                'cashier_deposit_id' => $cashier_deposit->id,
            ]);

            // 7. Hapus file temporary
            Storage::disk('public')->delete($filePath);
        }
        // foreach ($data['cashier_images'] as $filePath) {
        //     CashierDepositImage::create([
        //         'image' => $filePath,
        //         'cashier_deposit_id' => $cashier_deposit->id,

        //     ]);
        // }
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
