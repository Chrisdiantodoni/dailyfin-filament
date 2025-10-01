<?php

namespace App\Observers;

use App\Models\cashier_takeout_money;
use App\Models\User;
use App\Notifications\NotificationSent;

class TakeoutMoneyObserver
{
    /**
     * Handle the cashier_takeout_money "created" event.
     */
    public function created(cashier_takeout_money $cashier_takeout_money): void
    {
        $recipients = User::whereHas("roles", function ($q) {
            return $q->whereIn('name', ['IT', 'Cashier', 'Finance Operation', 'Finance Spv']);
        })->whereHas('dealer_users', function ($q) use ($cashier_takeout_money) {
            return $q->whereIn('dealer_code', [$cashier_takeout_money->dealer_code]);
        })->get();

        foreach ($recipients as $recipient) {
            $message = (object)[
                'user_id' => $recipient->id,
                'title' => ' Laporan Keluar Uang Brankas',
                'text' => " Laporan Keluar Uang Brankas dari {$cashier_takeout_money->dealers->dealer_name} menunggu pengecekan dari Fin Opr"
            ];
            $recipient->notify(new NotificationSent(
                $message,
            ));
        }
    }

    /**
     * Handle the cashier_takeout_money "updated" event.
     */
    public function updated(cashier_takeout_money $cashier_takeout_money): void
    {
        if ($cashier_takeout_money->status == 'request') {
            $recipients = User::whereHas("roles", function ($q) {
                return $q->whereIn('name', ['IT', 'Cashier', 'Finance Operation', 'Finance Spv']);
            })->whereHas('dealer_users', function ($q) use ($cashier_takeout_money) {
                return $q->whereIn('dealer_code', [$cashier_takeout_money->dealer_code]);
            })->get();

            foreach ($recipients as $recipient) {
                $message = (object)[
                    'user_id' => $recipient->id,
                    'title' => 'Keluar Uang Brankas',
                    'text' => "Laporan Keluar uang dari Brankas direvisi {$cashier_takeout_money->dealers->dealer_name} menunggu pengecekan dari Fin Opr"
                ];
                $recipient->notify(new NotificationSent(
                    $message,
                ));
            }
        } else {

            $recipients = User::whereHas("roles", function ($q) {
                return $q->whereIn('name', ['IT', 'Cashier', 'Finance Operation', 'Finance Spv']);
            })->whereHas('dealer_users', function ($q) use ($cashier_takeout_money) {
                return $q->whereIn('dealer_code', [$cashier_takeout_money->dealer_code]);
            })->get();

            foreach ($recipients as $recipient) {
                $message = (object) [
                    'user_id' => $recipient->id,
                    'title' => 'Keluar uang dari Brankas',
                    'text' => $cashier_takeout_money->status == 'reject' ? 'Fin Opr Reject Laporan Keluar Uang Brankas' : 'Fin Opr Approve Laporan Keluar Uang Brankas'
                ];
                $recipient->notify(new NotificationSent(
                    $message,
                ));
            }
        }
    }

    /**
     * Handle the cashier_takeout_money "deleted" event.
     */
    public function deleted(cashier_takeout_money $cashier_takeout_money): void
    {
        //
    }

    /**
     * Handle the cashier_takeout_money "restored" event.
     */
    public function restored(cashier_takeout_money $cashier_takeout_money): void
    {
        //
    }

    /**
     * Handle the cashier_takeout_money "force deleted" event.
     */
    public function forceDeleted(cashier_takeout_money $cashier_takeout_money): void
    {
        //
    }
}
