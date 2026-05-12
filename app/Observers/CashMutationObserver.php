<?php

namespace App\Observers;

use App\Models\CashMutate;
use App\Models\User;
use App\Notifications\NotificationSent;
use Carbon\Carbon;

class CashMutationObserver
{
    /**
     * Handle the CashMutate "created" event.
     */
    public function created(CashMutate $cashMutate): void
    {

        $createdAt = Carbon::parse($cashMutate->created_at);

        // cek apakah telat
        $isLate = $createdAt->greaterThan(
            $createdAt->copy()->setTime(19, 0)
        );
        $recipients = User::whereHas("roles", function ($q) {
            return $q->whereIn('name', ['IT', 'Finance Spv', 'Finance Operation']);
        })->whereHas('dealer_users', function ($q) use ($cashMutate) {
            return $q->whereIn('dealer_code', [$cashMutate->dealer_code]);
        })->get();

        foreach ($recipients as $recipient) {
            $message = (object)[
                'user_id' => $recipient->id,
                'title' => 'Laporan Mutasi Kas',
                'text' => "Laporan Mutasi Kas dari {$cashMutate->dealers->dealer_name} menunggu pengecekan dari Finance Spv"
            ];
            $recipient->notify(new NotificationSent(
                $message,
            ));
        }

        if ($isLate) {

            $coordinators = User::whereHas("roles", function ($q) {
                $q->where('name', 'Audit Coordinator');
            })
                ->whereHas('dealer_users', function ($q) use ($cashMutate) {
                    $q->whereIn('dealer_code', [$cashMutate->dealer_code]);
                })
                ->get();

            foreach ($coordinators as $coordinator) {
                $message = (object)[
                    'user_id' => $coordinator->id,
                    'title'   => 'Laporan Mutasi Kas TELAT',
                    'text'    => "⚠️ Setoran dari {$cashMutate->dealers->dealer_name} TELAT masuk (dibuat {$createdAt->format('H:i')}).",
                ];

                $coordinator->notify(new NotificationSent($message));
            }
        }
    }

    /**
     * Handle the CashMutate "updated" event.
     */
    public function updated(CashMutate $cashMutate): void
    {
        if (($cashMutate->incomes_neqs > 0 || $cashMutate->expenses_neqs > 0) && $cashMutate->status === 'approve') {
            $recipients = User::whereHas("roles", function ($q) {
                return $q->whereIn('name', ['IT', 'Audit Coordinator', 'Finance Spv', 'Finance Operation']);
            })->whereHas('dealer_users', function ($q) use ($cashMutate) {
                return $q->whereIn('dealer_code', [$cashMutate->dealer_code]);
            })->get();

            foreach ($recipients as $recipient) {
                $message = (object)[
                    'user_id' => $recipient->id,
                    'title' => 'Laporan Mutasi Kas',
                    'text' => " {$cashMutate->dealers->dealer_name} melakukan penginputan setoran neq"
                ];
                $recipient->notify(new NotificationSent(
                    $message,
                ));
            }
        } else if ($cashMutate->status == 'request') {
            $recipients = User::whereHas("roles", function ($q) {
                return $q->whereIn('name', ['IT', 'Cashier', 'Finance Operation', 'Finance Spv']);
            })->whereHas('dealer_users', function ($q) use ($cashMutate) {
                return $q->whereIn('dealer_code', [$cashMutate->dealer_code]);
            })->get();

            foreach ($recipients as $recipient) {
                $message = (object)[
                    'user_id' => $recipient->id,
                    'title' => 'Laporan Mutasi Kas',
                    'text' => "Laporan Mutasi Kas direvisi {$cashMutate->dealers->dealer_name} menunggu pengecekan dari Fin Spv"
                ];
                $recipient->notify(new NotificationSent(
                    $message,
                ));
            }
        } else {
            if ($cashMutate->status_deadline == "Late") {
                $coordinators = User::whereHas("roles", function ($q) {
                    $q->where('name', 'Audit Coordinator');
                })
                    ->whereHas('dealer_users', function ($q) use ($cashMutate) {
                        $q->whereIn('dealer_code', [$cashMutate->dealer_code]);
                    })
                    ->get();

                foreach ($coordinators as $coordinator) {
                    $message = (object)[
                        'user_id' => $coordinator->id,
                        'title'   => 'Approval Laporan Mutasi Kas TELAT',
                        'text'    => "⚠️ Mutasi Kas dari {$cashMutate->dealers->dealer_name} TELAT diapprove (dibuat {$cashMutate->updated_at->format('H:i')}).",
                    ];

                    $coordinator->notify(new NotificationSent($message));
                }
            }
            $recipients = User::whereHas("roles", function ($q) {
                return $q->whereIn('name', ['IT', 'Cashier', 'Finance Operation', 'Finance Spv']);
            })->whereHas('dealer_users', function ($q) use ($cashMutate) {
                return $q->whereIn('dealer_code', [$cashMutate->dealer_code]);
            })->get();

            foreach ($recipients as $recipient) {
                $message = (object) [
                    'user_id' => $recipient->id,
                    'title' => 'Laporan Mutasi Kas',
                    'text' => $cashMutate->status == 'reject' ? 'Fin Spv Reject laporan mutasi kas' : 'Fin Spv Approve laporan mutasi kas'
                ];
                $recipient->notify(new NotificationSent(
                    $message,
                ));
            }
        }
    }

    /**
     * Handle the CashMutate "deleted" event.
     */
    public function deleted(CashMutate $cashMutate): void
    {
        //
    }

    /**
     * Handle the CashMutate "restored" event.
     */
    public function restored(CashMutate $cashMutate): void
    {
        //
    }

    /**
     * Handle the CashMutate "force deleted" event.
     */
    public function forceDeleted(CashMutate $cashMutate): void
    {
        //
    }
}
