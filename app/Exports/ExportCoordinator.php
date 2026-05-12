<?php

namespace App\Exports;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ExportCoordinator implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @return \Illuminate\Support\Collection
     */
    private $startDate;
    private $endDate;

    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }
    public function collection()
    {
        $startDate = $this->startDate;
        $endDate = $this->endDate;
        $dealerCodes = Auth::user()->dealer_users()->pluck('dealer_code')->all();
        return  DB::table("cashier_deposits")
            ->select(
                DB::raw('DISTINCT dealers.dealer_code as dealer_code'),
                'coordinators.status as status',
                'dealers.dealer_name',
                'cashier_deposits.date_published',
                'cashier_deposits.start_balance as start_balance_cashier',
                'cashier_deposits.end_balance as end_balance_cashier',
                'cashier_deposits.invoice as invoice_cashier',
                'cash_mutates.start_balance as start_balance_mutates',
                'cash_mutates.end_balance as end_balance_mutates',
                'cash_mutates.invoice_nominal as invoice_mutates',
                'cashier_deposits.created_at',
                'cash_mutates.created_at'
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
            ->WhereIn('cash_mutates.id', function ($query) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('cash_mutates')
                    ->groupBy('dealer_code', 'date_published');
            })->whereIn('cashier_deposits.dealer_code', $dealerCodes)
            ->when($this->startDate && $this->endDate, function ($query) use ($startDate, $endDate) {
                return $query->whereBetween('cashier_deposits.date_published', [$startDate, $endDate]);
            })
            ->orderBy('cashier_deposits.created_at', 'desc')->orderBy('cash_mutates.created_at', 'desc')
            ->get();
    }

    public function map($row): array
    {

        return [
            $row->date_published,
            $row->dealer_name,
            $row->start_balance_cashier,
            $row->end_balance_cashier,
            $row->invoice_cashier,
            $row->start_balance_mutates,
            $row->end_balance_cashier,
            $row->invoice_mutates,
            $row->start_balance_cashier - $row->start_balance_mutates,
            $row->end_balance_cashier - $row->end_balance_mutates,
            $row->invoice_cashier - $row->invoice_mutates,
        ];
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Nama Dealer',
            'Saldo Awal Kasir',
            'Saldo Akhir Kasir',
            'Kasbon Gantung',
            'Saldo Awal Mutasi',
            'Saldo Akhir Mutasi',
            'Kasbon Gantung Mutasi',
            'Selisih Saldo Awal',
            'Selisih Saldo Akhir',
            'Selisih Kasbon Gantung',
            // Add other headings
        ];
    }
}
