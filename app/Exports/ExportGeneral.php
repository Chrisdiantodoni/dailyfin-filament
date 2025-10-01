<?php

namespace App\Exports;

use App\Models\CsServiceSparepart;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ExportGeneral implements FromCollection, WithHeadings, WithMapping
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
        return CsServiceSparepart::with('users', 'dealers', 'service_nominal_dtls', 'approval_cs_cashiers')->whereIn('dealer_code', $dealerCodes)
            ->when($this->startDate && $this->endDate, function ($query) use ($startDate, $endDate) {
                return $query->whereBetween('date_published', [$startDate, $endDate]);
            })->latest()
            ->get();
    }
    public function map($row): array
    {
        $lastApprovalUpdate = optional($row->approval_cs_cashiers->last())->updated_at;
        $lastUpdateUserName = optional($row->approval_cs_cashiers->last())->user->name ?? "";


        return [
            $row->date_published,
            optional($row->dealers)->dealer_name, // Use optional() to handle null values
            $row->users->name,
            $row->service_nominal_dtls->cash,
            $row->service_nominal_dtls->transfer,
            $row->total_expense,
            $row->total_income,
            $row->status,
            $row->description,
            $row->created_at,
            $lastApprovalUpdate,
            $lastUpdateUserName
        ];
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Nama Dealer',
            'Nama',
            'Setoran Tunai',
            'Setoran Transfer',
            'Nominal Pengeluaran',
            'Total Disetor',
            'Status',
            'Deskripsi',
            'Waktu Submit',
            'Waktu Update Terakhir',
            'Last User Update'
        ];
    }
}
