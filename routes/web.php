<?php

use App\Http\Controllers\ExportPdfControler;
use App\Http\Controllers\UploadedFileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/app/login');
});

Route::get('/login', function () {
    return redirect('/app/login');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/uploads/{path}', [UploadedFileController::class, 'show'])
        ->where('path', '.*')
        ->name('uploads.show');

    Route::get('/export/general/pdf', [ExportPdfControler::class, 'exportPdfGeneral'])->name('export.general.pdf');
    Route::get('/export/unit/pdf', [ExportPdfControler::class, 'exportPdfUnit'])->name('export.unit.pdf');
    Route::get('/export/cashier-deposit/pdf', [ExportPdfControler::class, 'exportPdfDeposit'])->name('export.cashier-deposit.pdf');
    Route::get('/export/cashier-takeout/pdf', [ExportPdfControler::class, 'exportPdfTakeout'])->name('export.cashier-takeout.pdf');
    Route::get('/export/validate/pdf', [ExportPdfControler::class, 'exportPdfValidate'])->name('export.validate.pdf');
    Route::get('/export/mutate/pdf', [ExportPdfControler::class, 'exportPdfMutate'])->name('export.mutate.pdf');
    Route::get('/print/cashier-deposit/pdf/{id}', [ExportPdfControler::class, 'printPdfDeposit'])->name('print.cashier-deposit.pdf');
    Route::get('/print/cashier-takeout/pdf/{id}', [ExportPdfControler::class, 'printPdfTakeout'])->name('print.cashier-takeout.pdf');
    Route::get('/print/validate/pdf/{id}', [ExportPdfControler::class, 'printPdfValidate'])->name('print.validate.pdf');
    Route::get('/print/cash-mutation/pdf/{id}', [ExportPdfControler::class, 'printPdfCashMutation'])->name('print.cash-mutation.pdf');
    Route::get('/print/coordinator/pdf', [ExportPdfControler::class, 'exportPdfCoordinator'])->name('export.coordinator.pdf');
});
