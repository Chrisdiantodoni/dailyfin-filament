<?php

namespace App\Observers;

use App\Models\CashierDeposit;
use App\Models\User;
use App\Notifications\NotificationSent;
use Carbon\Carbon;

class CashierDepositObserver
{
    /**
     * Handle the CashierDeposit "created" event.
     */
    public function created(CashierDeposit $cashierDeposit): void
    {
        $createdAt = Carbon::parse($cashierDeposit->created_at);

        // cek apakah telat
        $isLate = $createdAt->greaterThan(
            $createdAt->copy()->setTime(19, 0)
        );

        // kirim notifikasi normal (FinOps, Cashier, IT, Spv)
        $recipients = User::whereHas("roles", function ($q) {
            $q->whereIn('name', [
                'IT',
                'Cashier',
                'Finance Operation',
                'Finance Spv'
            ]);
        })
            ->whereHas('dealer_users', function ($q) use ($cashierDeposit) {
                $q->whereIn('dealer_code', [$cashierDeposit->dealer_code]);
            })
            ->get();

        foreach ($recipients as $recipient) {
            $message = (object)[
                'user_id' => $recipient->id,
                'title'   => 'Setoran Harian Brankas',
                'text'    => "Setoran harian brankas dari {$cashierDeposit->dealers->dealer_name} menunggu pengecekan dari FinOps.",
            ];

            $recipient->notify(new NotificationSent($message));
        }

        // kalau telat -> tambahan notifikasi ke Audit Coordinator
        if ($isLate) {

            $coordinators = User::whereHas("roles", function ($q) {
                $q->where('name', 'Audit Coordinator');
            })
                ->whereHas('dealer_users', function ($q) use ($cashierDeposit) {
                    $q->whereIn('dealer_code', [$cashierDeposit->dealer_code]);
                })
                ->get();

            foreach ($coordinators as $coordinator) {
                $message = (object)[
                    'user_id' => $coordinator->id,
                    'title'   => 'Setoran Harian Brankas TELAT',
                    'text'    => "⚠️ Setoran dari {$cashierDeposit->dealers->dealer_name} TELAT masuk (dibuat {$createdAt->format('H:i')}).",
                ];

                $coordinator->notify(new NotificationSent($message));
            }
        }
    }


    /**
     * Handle the CashierDeposit "updated" event.
     */
    public function updated(CashierDeposit $cashierDeposit): void
    {
        if ($cashierDeposit->status == 'request') {
            $recipients = User::whereHas("roles", function ($q) {
                return $q->whereIn('name', ['IT', 'Cashier', 'Finance Operation', 'Finance Spv']);
            })->whereHas('dealer_users', function ($q) use ($cashierDeposit) {
                return $q->whereIn('dealer_code', [$cashierDeposit->dealer_code]);
            })->get();

            foreach ($recipients as $recipient) {
                $message = (object)[
                    'user_id' => $recipient->id,
                    'title' => 'Setoran Harian Brankas',
                    'text' => "Setoran harian brankas direvisi {$cashierDeposit->dealers->dealer_name} menunggu pengecekan dari Fin Opr"
                ];
                $recipient->notify(new NotificationSent(
                    $message,
                ));
            }
        } else {

            $recipients = User::whereHas("roles", function ($q) {
                return $q->whereIn('name', ['IT', 'Cashier', 'Finance Operation', 'Finance Spv']);
            })->whereHas('dealer_users', function ($q) use ($cashierDeposit) {
                return $q->whereIn('dealer_code', [$cashierDeposit->dealer_code]);
            })->get();

            foreach ($recipients as $recipient) {
                $message = (object) [
                    'user_id' => $recipient->id,
                    'title' => 'Setoran Harian Brankas',
                    'text' => $cashierDeposit->status == 'reject' ? 'Fin Opr Reject Setoran Harian Brankas' : 'Fin Opr Approve Setoran Harian Brankas'
                ];
                $recipient->notify(new NotificationSent(
                    $message,
                ));
            }
        }
    }

    /**
     * Handle the CashierDeposit "deleted" event.
     */
    public function deleted(CashierDeposit $cashierDeposit): void
    {
        //
    }

    /**
     * Handle the CashierDeposit "restored" event.
     */
    public function restored(CashierDeposit $cashierDeposit): void
    {
        //
    }

    /**
     * Handle the CashierDeposit "force deleted" event.
     */
    public function forceDeleted(CashierDeposit $cashierDeposit): void
    {
        //
    }
}
