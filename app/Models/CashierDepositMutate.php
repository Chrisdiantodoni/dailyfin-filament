<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CashierDepositMutate extends Model
{
    protected $table = 'cashier_deposits';
    public $timestamps = false;
    protected $guarded = [];

    // Tambahkan ini biar Filament tahu pakai kolom "id"
    protected $primaryKey = 'id';

    // Kalau id di table bukan auto increment, set:
    public $incrementing = true;
    protected $keyType = 'string';
    public static function getReportQuery(?string $startDate = null, ?string $endDate = null)
    {
        $dealerCodes = Auth::user()->dealer_users->pluck('dealers.dealer_code')->toArray();
        // 1. Set default seminggu jika parameter kosong
        $startDate = $startDate ?: now()->subDays(7)->toDateString();
        $endDate = $endDate ?: now()->toDateString();

        $query = static::query()
            ->select(
                'cashier_deposits.id',
                DB::raw('dealers.dealer_code as dealer_code'),
                DB::raw('COALESCE(coordinators.status, "") as coordinator_status'),
                'dealers.dealer_name',
                'cashier_deposits.date_published',
                'cashier_deposits.start_balance as start_balance_cashier',
                'cashier_deposits.end_balance as end_balance_cashier',
                'cashier_deposits.invoice as invoice_cashier',
                'cash_mutates.start_balance as start_balance_mutates',
                'cash_mutates.end_balance as end_balance_mutates',
                'cash_mutates.invoice_nominal as invoice_mutates',
                'cashier_deposits.created_at as created_at'
            )
            ->join('dealers', 'cashier_deposits.dealer_code', '=', 'dealers.dealer_code')
            // 2. Filter tanggal harus ada di tabel utama (cashier_deposits)
            ->whereBetween('cashier_deposits.date_published', [$startDate, $endDate])
            ->whereIn('cashier_deposits.dealer_code', $dealerCodes)

            // 3. Ambil baris terbaru dari cashier_deposits
            ->whereIn('cashier_deposits.id', function ($query) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('cashier_deposits')
                    ->groupBy('dealer_code', 'date_published');
            })

            // 4. Join Mutasi dan Coordinator
            ->leftJoin('cash_mutates', function ($join) {
                $join->on('cashier_deposits.dealer_code', '=', 'cash_mutates.dealer_code')
                    ->on('cashier_deposits.date_published', '=', 'cash_mutates.date_published');
            })
            ->leftJoin('coordinators', function ($join) {
                $join->on('cashier_deposits.dealer_code', '=', 'coordinators.dealer_code')
                    ->on('cashier_deposits.date_published', '=', 'coordinators.date_published');
            })

            /* CATATAN: Jangan gunakan whereIn untuk cash_mutates.id di sini
           karena akan membuang data cashier yang belum ada mutasinya.
           Sebagai gantinya, pastikan data yang di-join adalah yang terbaru.
        */
            ->orderBy('cashier_deposits.date_published', 'desc')
            ->orderBy('cashier_deposits.created_at', 'desc');

        return $query;
    }
}
