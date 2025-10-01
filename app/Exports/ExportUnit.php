<?php

namespace App\Exports;

use App\Models\CsUnit;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ExportUnit implements FromCollection, WithHeadings, WithMapping
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
        $dealerCodes = Auth::user()->dealer_users->pluck('dealers.dealer_code')->toArray();
        return CsUnit::with('users', 'dealers', 'unit_nominal_dtls')->whereIn('dealer_code', $dealerCodes)
            ->when($this->startDate && $this->endDate, function ($query) use ($startDate, $endDate) {
                return $query->whereBetween('date_published', [$startDate, $endDate]);
            })->latest()
            ->get();
    }
    public function map($row): array
    {

        return [
            $row->date_published,
            optional($row->dealers)->dealer_name, // Use optional() to handle null values
            $row->users->name,
            $row->unit_nominal_dtls->cash,
            $row->unit_nominal_dtls->transfer,
            $row->total_expense,
            $row->total_income,
            $row->status,
            $row->description,
            $row->created_at,
            $row->updated_at,
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
        ];
    }
}
