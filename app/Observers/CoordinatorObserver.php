<?php

namespace App\Observers;

use App\Models\Coordinator;
use App\Models\User;
use App\Notifications\NotificationSent;

class CoordinatorObserver
{
    /**
     * Handle the Coordinator "created" event.
     */
    public function created(Coordinator $coordinator): void
    {
        //
    }

    /**
     * Handle the Coordinator "updated" event.
     */
    public function updated(Coordinator $coordinator): void
    {
        if ($coordinator->status == 'approve') {
            $recipients = User::whereHas("roles", function ($q) {
                return $q->whereIn('name', ['IT', 'Audit Coordinator', 'Finance Manager']);
            })->whereHas('dealer_users', function ($q) use ($coordinator) {
                return $q->whereIn('dealer_code', [$coordinator->dealer_code]);
            })->get();

            foreach ($recipients as $recipient) {
                $message = (object)[
                    'user_id' => $recipient->id,
                    'title' => 'Laporan Saldo Selisih Dikonfirmasi',
                    'text' => "Laporan Saldo Selisih {$coordinator->dealers->dealer_name} Disetujui"
                ];
                $recipient->notify(new NotificationSent(
                    $message,
                ));
            }
        }
    }

    /**
     * Handle the Coordinator "deleted" event.
     */
    public function deleted(Coordinator $coordinator): void
    {
        //
    }

    /**
     * Handle the Coordinator "restored" event.
     */
    public function restored(Coordinator $coordinator): void
    {
        //
    }

    /**
     * Handle the Coordinator "force deleted" event.
     */
    public function forceDeleted(Coordinator $coordinator): void
    {
        //
    }
}
