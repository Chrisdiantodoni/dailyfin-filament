<?php

namespace App\Exports;

use App\Models\CashMutate;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ExportMutate implements FromCollection, WithHeadings, WithMapping
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
        $dealerCodes = Auth::user()->dealer_users->pluck('dealers.dealer_code')->toArray();
        return CashMutate::with('users', 'dealers', 'approval_mutate', 'approval_mutate.user')->whereIn('dealer_code', $dealerCodes)
            ->when($this->startDate && $this->endDate, function ($query) use ($startDate, $endDate) {
                return $query->whereBetween('date_published', [$startDate, $endDate]);
            })->latest()
            ->get();
    }
    public function map($row): array
    {
        $lastApprovalUpdate = optional($row->approval_mutate->last())->updated_at;
        $lastUserUpdate = optional($row->approval_mutate->last())->user->name ?? "-";
        $createdAt = \Carbon\Carbon::parse($row->created_at);
        $isLate = $createdAt->format('H:i') > '19:00';

        return [
            $row->date_published,
            optional($row->dealers)->dealer_name,
            $row->start_balance,
            $row->income,
            $row->expense,
            $row->end_balance,
            $row->invoice_nominal,
            $row->physical_cash,
            $row->status,
            $row->description,
            $row->created_at,
            $lastApprovalUpdate,
            $lastUserUpdate,
            $isLate ? "Late" : "On-time",
            $row->status_deadline ?? 'Need Approval',
        ];
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Nama Dealer',
            'Saldo Awal',
            'Penerimaan',
            'Pengeluaran',
            'Saldo Akhir',
            'Kasbon Gantung',
            'Fisik Kas',
            'Status',
            'Keterangan',
            'Waktu Submit',
            'Waktu Update Terakhir',
            'Last User Update',
            'Status Deadline Submit',
            'Status Deadline Approval'
        ];
    }
}
