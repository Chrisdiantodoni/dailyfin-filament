<?php

namespace App\Observers;

use App\Models\User;
use App\Models\ValidationDeposit;
use App\Notifications\NotificationSent;

class ValidationDepositObserver
{
    /**
     * Handle the ValidationDeposit "created" event.
     */
    public function created(ValidationDeposit $validationDeposit): void
    {
        $recipients = User::whereHas("roles", function ($q) {
            return $q->whereIn('name', ['IT', 'Finance Spv', 'Finance Operation']);
        })->whereHas('dealer_users', function ($q) use ($validationDeposit) {
            return $q->whereIn('dealer_code', [$validationDeposit->dealer_code]);
        })->get();

        foreach ($recipients as $recipient) {
            $message = (object)[
                'user_id' => $recipient->id,
                'title' => 'Validasi Setoran',
                'text' => "Validasi Setoran dari {$validationDeposit->dealers->dealer_name} menunggu pengecekan dari Finance Spv"
            ];
            $recipient->notify(new NotificationSent(
                $message,
            ));
        }
    }

    /**
     * Handle the ValidationDeposit "updated" event.
     */
    public function updated(ValidationDeposit $validationDeposit): void
    {
        if ($validationDeposit->status == 'request') {
            $recipients = User::whereHas("roles", function ($q) {
                return $q->whereIn('name', ['IT', 'Cashier', 'Finance Operation', 'Finance Spv']);
            })->whereHas('dealer_users', function ($q) use ($validationDeposit) {
                return $q->whereIn('dealer_code', [$validationDeposit->dealer_code]);
            })->get();

            foreach ($recipients as $recipient) {
                $message = (object)[
                    'user_id' => $recipient->id,
                    'title' => 'Validasi Setoran',
                    'text' => "Validasi Setoran direvisi {$validationDeposit->dealers->dealer_name} menunggu pengecekan dari Fin Spv"
                ];
                $recipient->notify(new NotificationSent(
                    $message,
                ));
            }
        } else {
            if ($validationDeposit->status_deadline == "Late") {
                $coordinators = User::whereHas("roles", function ($q) {
                    $q->where('name', 'Audit Coordinator');
                })
                    ->whereHas('dealer_users', function ($q) use ($validationDeposit) {
                        $q->whereIn('dealer_code', [$validationDeposit->dealer_code]);
                    })
                    ->get();

                foreach ($coordinators as $coordinator) {
                    $message = (object)[
                        'user_id' => $coordinator->id,
                        'title'   => 'Approval Validasi Setoran TELAT',
                        'text'    => "⚠️ Validasi Setoran dari {$validationDeposit->dealers->dealer_name} TELAT approve (dibuat {$validationDeposit->updated_at->format('H:i')}).",
                    ];

                    $coordinator->notify(new NotificationSent($message));
                }
            }
            $recipients = User::whereHas("roles", function ($q) {
                return $q->whereIn('name', ['IT', 'Cashier', 'Finance Operation', 'Finance Spv']);
            })->whereHas('dealer_users', function ($q) use ($validationDeposit) {
                return $q->whereIn('dealer_code', [$validationDeposit->dealer_code]);
            })->get();

            foreach ($recipients as $recipient) {
                $message = (object) [
                    'user_id' => $recipient->id,
                    'title' => 'Validasi Setoran',
                    'text' => $validationDeposit->status == 'reject' ? 'Fin Spv Reject Validasi Setoran' : 'Fin Spv Approve Validasi Setoran'
                ];
                $recipient->notify(new NotificationSent(
                    $message,
                ));
            }
        }
    }

    /**
     * Handle the ValidationDeposit "deleted" event.
     */
    public function deleted(ValidationDeposit $validationDeposit): void
    {
        //
    }

    /**
     * Handle the ValidationDeposit "restored" event.
     */
    public function restored(ValidationDeposit $validationDeposit): void
    {
        //
    }

    /**
     * Handle the ValidationDeposit "force deleted" event.
     */
    public function forceDeleted(ValidationDeposit $validationDeposit): void
    {
        //
    }
}
