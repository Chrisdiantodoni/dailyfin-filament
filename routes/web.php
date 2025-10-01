<?php

use App\Events\NotificationSent;
use App\Filament\Pages\CoordinatorDetail;
use App\Http\Controllers\ExportPdfControler;
use App\Models\User;
use App\Notifications\NotificationSent as NotificationsNotificationSent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/app/login');
});


Route::get('/login', function () {
    return redirect('/app/login');
});

Route::get('/test-event', function () {
    // $recipients = User::whereHas("roles", function ($q) {
    //     return $q->whereIn('name', ['IT', 'Cashier']);
    // })->get();

    // // foreach ($recipients as $recipient) {
    // $message = (object)[
    //     'user_id' => Auth::user()->id,
    //     'text' => "Laporan dari counter {kode} menunggu pengecekan."
    // ];
    // // $recipient->notify(new BulkCounterServiceNotification(
    // //     $message
    // // ));
    // event(new NotificationSent($message));
    // // NotificationSent::dispatch($message);

    // // dd($message);
    // // }

    $recipients = User::whereHas("roles", function ($q) {
        return $q->whereIn('name', ['IT', 'Cashier']);
    })->get();

    $message = (object)[
        'text' => "Laporan dari counter {kode} menunggu pengecekan."
    ];
    // $this->dispatchBrowserEvent('notify', [
    //     'title' => 'Notifikasi Baru',
    //     'body' => 'Ada notifikasi baru dari server',
    // ]);

    // ✅ KIRIM SEKALIGUS KE SEMUA USER
    $recipients->each(function ($recipient) {
        $message = (object)[
            'user_id' => $recipient->id,
            'text' => "Laporan dari counter menunggu pengecekan oleh " . $recipient->name
        ];
        $user = User::first();
        broadcast(new \Filament\Notifications\Events\DatabaseNotificationsSent($user));

        $recipient->notify(new NotificationsNotificationSent($message));
    });

    return 'Event triggered!';
});

Route::get('/test-broadcast', function () {
    $recipients = User::whereHas("roles", function ($q) {
        return $q->whereIn('name', ['IT', 'Cashier']);
    })->get();

    $message = (object)[
        'text' => "Laporan dari counter {kode} menunggu pengecekan."
    ];
    // $this->dispatchBrowserEvent('notify', [
    //     'title' => 'Notifikasi Baru',
    //     'body' => 'Ada notifikasi baru dari server',
    // ]);

    // ✅ KIRIM SEKALIGUS KE SEMUA USER
    $recipients->each(function ($recipient) {
        $message = (object)[
            'user_id' => $recipient->id,
            'text' => "Laporan dari counter menunggu pengecekan oleh " . $recipient->name
        ];

        $recipient->notify(new NotificationsNotificationSent($message));
    });

    return 'Test broadcast sent!';
});


Route::get('/coordinator-detail', [CoordinatorDetail::class, 'render'])
    ->name('filament.pages.coordinator-detail');
// routes/web.php
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
