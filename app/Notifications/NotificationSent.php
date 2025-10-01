<?php

namespace App\Notifications;

use Filament\Livewire\Notifications;
use Filament\Notifications\Notification as NotificationsNotification;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NotificationSent extends Notification implements ShouldBroadcast
{
    use Queueable;

    public function __construct(public $message, public $url = null)
    {
        $this->url = $url ?? route('filament.app.resources.counter-service-deposits.index');
    }
    // Set timeout yang lebih panjang (dalam detik)

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }


    public function toDatabase(object $notifiable): array
    {
        return NotificationsNotification::make()
            ->title($this->message->title)
            ->body($this->message->text)
            ->getDatabaseMessage();
    }
    public function broadcastOn()
    {
        return new PrivateChannel('App.Models.User.' . $this->message->user_id);
    }
    public function toBroadcast(object $notifiable): BroadcastMessage
    {


        return NotificationsNotification::make()
            ->title($this->message->title)
            ->body($this->message->text)
            ->getBroadcastMessage();
    }

    public function broadcastType()
    {
        return 'NotificationSent';
    }
    public function broadcastAs()
    {
        return 'NotificationSent';
    }
}
