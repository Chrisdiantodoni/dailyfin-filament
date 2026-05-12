<?php

namespace App\Exports;

use App\Models\ValidationDeposit;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ExportValidate implements FromCollection, WithHeadings, WithMapping
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
        return ValidationDeposit::with('users', 'dealers', 'approval_validations')->whereIn('dealer_code', $dealerCodes)
            ->when($this->startDate && $this->endDate, function ($query) use ($startDate, $endDate) {
                return $query->whereBetween('date_published', [$startDate, $endDate]);
            })->latest()
            ->get();
    }
    public function map($row): array
    {

        $lastApprovalUpdate = optional($row->approval_validations->last())->updated_at;
        $lastUserUpdate = optional($row->approval_validations->last())->user->name ?? "";
        $createdAt = \Carbon\Carbon::parse($row->created_at);
        $isLate = isLateValidateDeposit($createdAt, $row->date_published);
        return [
            $row->date_published,
            optional($row->dealers)->dealer_name,
            $row->neq_name,
            $row->customer_name,
            $row->nominal_deposit,
            $row->bank_name,
            $row->transaction_type,
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
            'Neq',
            'Nama Dealer',
            'Nama Penyetor',
            'Nominal Setoran',
            'Nama Bank',
            'Jenis Transaksi',
            'Status',
            'Deskripsi',
            'Waktu Submit',
            'Waktu Update Terakhir',
            'Last User Update',
            'Status Deadline Submit',
            'Status Deadline Approval'
        ];
    }
}
