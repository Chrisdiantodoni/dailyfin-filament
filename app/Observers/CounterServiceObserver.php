<?php

namespace App\Observers;

use App\Models\CsServiceSparepart;
use App\Models\User;
use App\Notifications\NotificationSent;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class CounterServiceObserver
{
    /**
     * Handle the CsServiceSparepart "created" event.
     */
    public function created(CsServiceSparepart $csServiceSparepart): void
    {
        $recipients = User::whereHas("roles", function ($q) {
            return $q->whereIn('name', ['IT', 'Cashier', 'Counter Service']);
        })->whereHas('dealer_users', function ($q) use ($csServiceSparepart) {
            return $q->whereIn('dealer_code', [$csServiceSparepart->dealer_code]);
        })->get();

        foreach ($recipients as $recipient) {
            $message = (object)[
                'user_id' => $recipient->id,
                'title' => 'Pendapatan Sparepart dan Jasa',
                'text' => "Laporan dari Counter Service {$csServiceSparepart->dealers->dealer_name} menunggu pengecekan dari Kasir"
            ];
            $recipient->notify(new NotificationSent(
                $message,
            ));
        }
    }

    public function updated(CsServiceSparepart $csServiceSparepart): void
    {
        // dd($csServiceSparepart);
        if ($csServiceSparepart->status == 'request') {
            $recipients = User::whereHas("roles", function ($q) {
                return $q->whereIn('name', ['IT', 'Cashier']);
            })->whereHas('dealer_users', function ($q) use ($csServiceSparepart) {
                return $q->whereIn('dealer_code', [$csServiceSparepart->dealer_code]);
            })->get();

            foreach ($recipients as $recipient) {
                $message = (object)[
                    'user_id' => $recipient->id,
                    'title' => 'Pendapatan Sparepart dan Jasa',
                    'text' => "Laporan dari Counter Service direvisi {$csServiceSparepart->dealers->dealer_name} menunggu pengecekan dari Kasir"
                ];
                $recipient->notify(new NotificationSent(
                    $message,
                ));
            }
        } else {
            $recipients = User::whereHas("roles", function ($q) {
                return $q->whereIn('name', ['IT', 'Cashier', 'Counter Service']);
            })->whereHas('dealer_users', function ($q) use ($csServiceSparepart) {
                return $q->whereIn('dealer_code', [$csServiceSparepart->dealer_code]);
            })->get();
            foreach ($recipients as $recipient) {
                $message = (object) [
                    'user_id' => $csServiceSparepart->user_id,
                    'title' => 'Pendapatan Sparepart dan Jasa',
                    'text' => $csServiceSparepart->status == 'reject' ? 'Cashier Reject Setoran Counter' : 'Kasir Approve Setoran Counter'
                ];
                $recipient->notify(new NotificationSent(
                    $message,
                ));
            }
        }
    }
}
