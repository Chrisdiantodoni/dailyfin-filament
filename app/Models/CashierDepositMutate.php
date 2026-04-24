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
    public  static function getReportQuery(?string $startDate = null, ?string $endDate = null, ?string $status = null)
    {
        $dealerCodes = Auth::user()->dealer_users->pluck('dealers.dealer_code')->toArray();

        $query = static::query()
            ->select(
                'cashier_deposits.id',
                DB::raw('dealers.dealer_code as dealer_code'),
                'coordinators.status as status',
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
            ->leftJoin('cash_mutates', function ($join) {
                $join->on('cashier_deposits.dealer_code', '=', 'cash_mutates.dealer_code')
                    ->on('cashier_deposits.date_published', '=', 'cash_mutates.date_published');
            })
            ->leftJoin('coordinators', function ($join) {
                $join->on('cashier_deposits.dealer_code', '=', 'coordinators.dealer_code')
                    ->on('cashier_deposits.date_published', '=', 'coordinators.date_published');
            })
            ->whereIn('cashier_deposits.id', function ($query) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('cashier_deposits')
                    ->groupBy('dealer_code', 'date_published');
            })
            ->whereIn('cash_mutates.id', function ($query) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('cash_mutates')
                    ->groupBy('dealer_code', 'date_published');
            })
            ->whereIn('cashier_deposits.dealer_code', $dealerCodes)
            ->whereIn('cashier_deposits.id', function ($query) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('cashier_deposits')
                    ->groupBy('cashier_deposits.dealer_code', 'cashier_deposits.date_published'); // Tambahkan prefix tabel
            })
            ->orderBy('cashier_deposits.created_at', 'desc')
            ->orderBy('cash_mutates.created_at', 'desc');

        // Apply range kalau dikasih
        if ($startDate && $endDate) {
            $query->whereBetween('cashier_deposits.date_published', [$startDate, $endDate]);
        }

        if ($status) {
            $query->where('coordinators.status', $status);
        }

        return $query;
    }
}
