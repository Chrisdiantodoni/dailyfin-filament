<?php

namespace App\Exports;

use App\Models\CashierDeposit;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ExportDeposit implements FromCollection, WithHeadings, WithMapping
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
        return CashierDeposit::latest()
            ->with(['approval_cashier', 'users', 'dealers', 'cashier_images'])
            ->whereIn('dealer_code', $dealerCodes)
            ->when($this->startDate && $this->endDate, function ($query) use ($startDate, $endDate) {
                return $query->whereBetween('date_published', [$startDate, $endDate]);
            })->latest()
            ->get();
    }
    public function map($row): array
    {

        $lastApprovalUpdate = optional($row->approval_cashier->last())->updated_at;
        $lastUserUpdate = optional($row->approval_cashier->last())->user->name ?? "";
        $createdAt = \Carbon\Carbon::parse($row->created_at);
        $isLate = $createdAt->format('H:i') > '19:00';



        return [
            $row->date_published,
            $row->users->name,
            optional($row->dealers)->dealer_name, // Use optional() to handle null values
            $row->start_balance,
            $row->today_income,
            $row->expense,
            $row->bank_deposit,
            $row->end_balance,
            $row->invoice,
            $row->total_deposit,
            $row->bank_name,
            $row->status,
            $row->description,
            $row->created_at,
            $lastApprovalUpdate, //
            $lastUserUpdate,
            $isLate ? "Late" : "On-time"
        ];
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Nama Lengkap',
            'Dealer',
            'Saldo Awal',
            'Penerimaan Hari ini',
            'Pengeluaran Hari ini',
            'Setoran Ke Bank',
            'Saldo Akhir',
            'Kasbon Gantung',
            'Total Setoran Ke Brankas',
            'Nama Bank',
            'Status',
            'Description',
            'Waktu Submit',
            'Waktu Update Terakhir',
            'Last User Update',
            'Status Deadline Submit',
            // Add other headings
        ];
    }
}
