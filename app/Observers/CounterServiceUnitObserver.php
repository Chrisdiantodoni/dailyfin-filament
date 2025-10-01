<?php

namespace App\Observers;

use App\Models\CsUnit;
use App\Models\User;
use App\Notifications\NotificationSent;

class CounterServiceUnitObserver
{
    /**
     * Handle the CsUnit "created" event.
     */
    public function created(CsUnit $csUnit): void
    {
        $recipients = User::whereHas("roles", function ($q) {
            return $q->whereIn('name', ['IT', 'Cashier', 'Counter Service']);
        })->whereHas('dealer_users', function ($q) use ($csUnit) {
            return $q->whereIn('dealer_code', [$csUnit->dealer_code]);
        })->get();

        foreach ($recipients as $recipient) {
            $message = (object)[
                'user_id' => $recipient->id,
                'title' => "Pendapatan Setoran Unit",
                'text' => "Laporan Setoran Unit dari Counter Service {$csUnit->dealers->dealer_name} menunggu pengecekan dari Kasir"
            ];
            $recipient->notify(new NotificationSent(
                $message,
            ));
        }
    }

    /**
     * Handle the CsUnit "updated" event.
     */
    public function updated(CsUnit $csUnit): void
    {
        if ($csUnit->status == 'request') {
            $recipients = User::whereHas("roles", function ($q) {
                return $q->whereIn('name', ['IT', 'Cashier', 'Counter Service']);
            })->whereHas('dealer_users', function ($q) use ($csUnit) {
                return $q->whereIn('dealer_code', [$csUnit->dealer_code]);
            })->get();

            foreach ($recipients as $recipient) {
                $message = (object)[
                    'user_id' => $recipient->id,
                    'title' => 'Pendapatan Setoran Unit',
                    'text' => "Laporan dari Counter Service direvisi {$csUnit->dealers->dealer_name} menunggu pengecekan dari Kasir"
                ];
                $recipient->notify(new NotificationSent(
                    $message,
                ));
            }
        } else {

            $recipients = User::whereHas("roles", function ($q) {
                return $q->whereIn('name', ['IT', 'Cashier', 'Counter Service']);
            })->whereHas('dealer_users', function ($q) use ($csUnit) {
                return $q->whereIn('dealer_code', [$csUnit->dealer_code]);
            })->get();

            // $message = (object) [
            //     'user_id' => $csUnit->user_id,
            //     'title' => 'Pendapatan Setoran Unit',
            //     'text' => $csUnit->status == 'reject' ? 'Cashier Reject Setoran Counter' : 'Kasir Approve Setoran Counter'
            // ];
            foreach ($recipients as $recipient) {
                $message = (object) [
                    'user_id' => $csUnit->user_id,
                    'title' => 'Pendapatan Setoran Unit',
                    'text' => $csUnit->status == 'reject' ? 'Cashier Reject Setoran Counter' : 'Kasir Approve Setoran Counter'
                ];
                $recipient->notify(new NotificationSent(
                    $message,
                ));
            }
        }
    }

    /**
     * Handle the CsUnit "deleted" event.
     */
    public function deleted(CsUnit $csUnit): void
    {
        //
    }

    /**
     * Handle the CsUnit "restored" event.
     */
    public function restored(CsUnit $csUnit): void
    {
        //
    }

    /**
     * Handle the CsUnit "force deleted" event.
     */
    public function forceDeleted(CsUnit $csUnit): void
    {
        //
    }
}
