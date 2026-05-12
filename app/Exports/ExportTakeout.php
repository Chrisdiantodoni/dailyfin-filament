<?php

namespace App\Exports;

use App\Models\cashier_takeout_money;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ExportTakeout implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @return \Illuminate\Support\Collection
     */ private $startDate;
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
        return cashier_takeout_money::with('users', 'dealers', 'approval_takeout_money')->whereIn('dealer_code', $dealerCodes)
            ->when($this->startDate && $this->endDate, function ($query) use ($startDate, $endDate) {
                return $query->whereBetween('date_published', [$startDate, $endDate]);
            })->latest()
            ->get();
    }
    public function map($row): array
    {

        $lastApprovalUpdate = optional($row->approval_takeout_money->last())->updated_at;
        $lastUserName = optional($row->approval_takeout_money->last())->user->name ?? "";
        $createdAt = \Carbon\Carbon::parse($row->created_at);
        $isLate = $createdAt->format('H:i') > '19:00';


        return [
            $row->date_published,
            optional($row->dealers)->dealer_name, // Use optional() to handle null values
            $row->users->name,
            $row->revised_finance_nominal,
            $row->end_balance,
            $row->money_put,
            $row->takeout_nominal,
            $row->revised_ops_nominal,
            $row->description,
            $row->status,
            $row->created_at,
            $lastApprovalUpdate,
            $lastUserName,
            $isLate ? "Late" : "On-time"

        ];
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Nama Dealer',
            'Nama',
            'Saldo Revisi',
            'Total Uang di Brankas',
            'Jumlah Uang Titipan',
            'Total Uang yang Dikeluarkan',
            'Revisi Jumlah Uang Dikeluarkan',
            'Keterangan',
            'Status',
            'Waktu Submit',
            'Waktu Update Terakhir',
            'Last User Update',
            'Status Deadline Submit'
        ];
    }
}
