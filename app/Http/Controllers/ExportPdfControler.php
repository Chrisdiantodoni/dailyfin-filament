<?php

namespace App\Http\Controllers;

use App\Models\cashier_takeout_money;
use App\Models\CashierDeposit;
use App\Models\CashMutate;
use App\Models\CsServiceSparepart;
use App\Models\CsUnit;
use App\Models\ValidationDeposit;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExportPdfControler extends Controller
{
    public function exportPdfGeneral()
    {
        $startDate = request('start_date');
        $endDate = request('end_date');
        $dealerCodes = Auth::user()->dealer_users->pluck('dealers.dealer_code')->toArray();
        $exportData = CsServiceSparepart::with('dealers', 'users', 'service_nominal_dtls')->whereIn('dealer_code', $dealerCodes)
            ->when($startDate & $endDate, function ($query) use ($startDate, $endDate) {
                $query->whereBetween('date_published', [$startDate, $endDate]);
            })->latest()->get();
        $pdf = Pdf::loadView('pdf.report.export_general', compact('exportData', 'startDate', 'endDate'))->setPaper('a4', 'landscape')->setOption([
            'tempDir' => public_path(),
            'chroot' => public_path()
        ]);

        return $pdf->stream('Report Setoran Jasa Service & Sparepart ke Kasir per Tanggal' . $startDate . ' - ' . $endDate . '.pdf');
    }
    public function exportPdfUnit()
    {
        $startDate = request('start_date');
        $endDate = request('end_date');
        $dealerCodes = Auth::user()->dealer_users->pluck('dealers.dealer_code')->toArray();
        $exportData = CsUnit::with('dealers', 'users', 'unit_nominal_dtls')
            ->latest()
            ->whereIn('dealer_code', $dealerCodes)
            ->when($startDate & $endDate, function ($query) use ($startDate, $endDate) {
                $query->whereBetween('date_published', [$startDate, $endDate]);
            })->get();

        $pdf = Pdf::loadView('pdf.report.export_unit', compact('exportData', 'startDate', 'endDate'))->setPaper('a4', 'landscape')->setOption([
            'tempDir' => public_path(),
            'chroot' => public_path()
        ]);
        return $pdf->stream('Report Setoran Jasa Service & Sparepart ke Kasir per Tanggal' . $startDate . ' - ' . $endDate . '.pdf');
    }
    public function exportPdfDeposit()
    {
        $startDate = request('start_date');
        $endDate = request('end_date');
        $dealerCodes = Auth::user()->dealer_users->pluck('dealers.dealer_code')->toArray();
        $exportData = CashierDeposit::latest()
            ->with(['approval_cashier', 'users', 'dealers', 'cashier_images'])
            ->whereIn('dealer_code', $dealerCodes)->when($startDate & $endDate, function ($query) use ($startDate, $endDate) {
                $query->whereBetween('date_published', [$startDate, $endDate]);
            })->latest()->get();

        $pdf = Pdf::loadView('pdf.report.export_deposit', compact('exportData', 'startDate', 'endDate'))->setPaper('a4', 'landscape')->setOption([
            'tempDir' => public_path(),
            'chroot' => public_path()
        ]);
        return $pdf->stream('Konfirmasi Laporan Kasir ke Ops & Spv per Tanggal' . $startDate . ' - ' . $endDate . '.pdf');
    }
    public function exportPdfTakeout()
    {
        $startDate = request('start_date');
        $endDate = request('end_date');
        $dealerCodes = Auth::user()->dealer_users->pluck('dealers.dealer_code')->toArray();
        $exportData = cashier_takeout_money::with('dealers', 'users')->whereIn('dealer_code', $dealerCodes)
            ->when($startDate & $endDate, function ($query) use ($startDate, $endDate) {
                $query->whereBetween('date_published', [$startDate, $endDate]);
            })->latest()->get();


        $pdf = Pdf::loadView('pdf.report.export_takeout', compact('exportData', 'startDate', 'endDate'))->setPaper('a4', 'landscape')->setOption([
            'tempDir' => public_path(),
            'chroot' => public_path()
        ]);

        return $pdf->stream('Konfirmasi Laporan Kasir ke Ops & Spv per Tanggal' . $startDate . ' - ' . $endDate . '.pdf');
    }
    public function exportPdfValidate()
    {
        $startDate = request('start_date');
        $endDate = request('end_date');
        $dealerCodes = Auth::user()->dealer_users->pluck('dealers.dealer_code')->toArray();
        $exportData = ValidationDeposit::with('dealers', 'users')->whereIn('dealer_code', $dealerCodes)
            ->when($startDate & $endDate, function ($query) use ($startDate, $endDate) {
                $query->whereBetween('date_published', [$startDate, $endDate]);
            })->latest()->get();

        $pdf = Pdf::loadView('pdf.report.export_validate', compact('exportData', 'startDate', 'endDate'))->setPaper('a4', 'landscape')->setOption([
            'tempDir' => public_path(),
            'chroot' => public_path()
        ]);

        return $pdf->stream('Report Validasi Setoran per Tanggal' . $startDate . ' - ' . $endDate . '.pdf');
    }
    public function exportPdfMutate()
    {

        $startDate = request('start_date');
        $endDate = request('end_date');
        $dealerCodes = Auth::user()->dealer_users->pluck('dealers.dealer_code')->toArray();
        $exportData = CashMutate::with('dealers', 'users',)->whereIn('dealer_code', $dealerCodes)
            ->when($startDate & $endDate, function ($query) use ($startDate, $endDate) {
                $query->whereBetween('date_published', [$startDate, $endDate]);
            })->latest()->get();

        $pdf = Pdf::loadView('pdf.report.export_mutate', compact('exportData', 'startDate', 'endDate'))->setPaper('a4', 'landscape')->setOption([
            'tempDir' => public_path(),
            'chroot' => public_path()
        ]);

        return $pdf->stream('Report Mutasi Kas per Tanggal' . $startDate . ' - ' . $endDate . '.pdf');
    }

    public function printPdfDeposit($id)
    {
        $exportData = CashierDeposit::findOrFail($id);
        $pdf = Pdf::loadView('pdf.detail.deposit_box', compact('exportData'))->setPaper('a4')->setOption([
            'tempDir' => public_path(),
            'chroot' => public_path(),
            'dpi' => 150
        ]);
        return $pdf->stream('LAPORAN BRANKAS - SORE ' . Carbon::now()  . '.pdf');
    }

    public function printPdfTakeout($id)
    {
        $exportData = cashier_takeout_money::findOrFail($id);
        $pdf = Pdf::loadView('pdf.detail.takeout', compact('exportData'))->setPaper('a4')->setOption([
            'tempDir' => public_path(),
            'chroot' => public_path(),
            'dpi' => 150
        ]);
        return $pdf->stream('LAPORAN BRANKAS - PAGI ' . Carbon::now()  . '.pdf');
    }

    public function printPdfValidate($id)
    {
        $exportData = ValidationDeposit::findOrFail($id);
        $pdf = Pdf::loadView('pdf.detail.validate_deposit', compact('exportData'))->setPaper('a4')->setOption([
            'tempDir' => public_path(),
            'chroot' => public_path(),
            'dpi' => 150
        ]);
        return $pdf->stream('VALIDASI SETORAN ' . $exportData->date_published  . '.pdf');
    }

    public function printPdfCashMutation($id)
    {
        $exportData = CashMutate::findOrFail($id);
        $pdf = Pdf::loadView('pdf.detail.cash_mutate', compact('exportData'))->setPaper('a4')->setOption([
            'tempDir' => public_path(),
            'chroot' => public_path(),
            'dpi' => 150
        ]);
        return $pdf->stream('MUTASI KAS ' . $exportData->date_published  . '.pdf');
    }

    public function exportPdfCoordinator()
    {

        $startDate = request('start_date');
        $endDate = request('end_date');
        $dealerCodes = Auth::user()->dealer_users->pluck('dealers.dealer_code')->toArray();
        $exportData = DB::table("cashier_deposits")
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
                'cashier_deposits.created_at', // Include cashier_deposits.created_at
                'cash_mutates.created_at' // Include cash_mutates.created_at
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
            })->whereIn('cashier_deposits.dealer_code', $dealerCodes)->whereBetween('cashier_deposits.date_published', [$startDate, $endDate])
            ->orderBy('cashier_deposits.created_at', 'desc')->orderBy('cash_mutates.created_at', 'desc')
            ->get();
        $pdf = Pdf::loadView('pdf.report.export_coordinator', compact('exportData', 'startDate', 'endDate'))->setPaper('a4', 'landscape')->setOption([
            'tempDir' => public_path(),
            'chroot' => public_path(),
            'dpi' => 150
        ]);
        return $pdf->stream('Report Coordinator' . $startDate . ' - ' . $endDate . '.pdf');
    }
}
