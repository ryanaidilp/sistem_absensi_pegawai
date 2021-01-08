<?php

namespace App\Notifications;

use App\Models\PaidLeave;
use App\Notifications\Channels\OneSignalChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaidLeaveExpiredNotification extends Notification
{
    use Queueable;

    private $paidLeave;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(PaidLeave $paidLeave)
    {
        $this->paidLeave = $paidLeave;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database', OneSignalChannel::class];
    }


    public function toDatabase($notifiable)
    {
        $date = today()->translatedFormat('l, d F Y');
        $cuti = $this->paidLeave;;
        return [
            'heading' => $cuti->kategori->name . " kedaluarsa. $date!",
            'body' => $cuti->kategori->name . " anda dengan subjek :\n{$cuti->title}\n\nTelah kedaluarsa. Status kehadiran anda akan kembali normal."
        ];
    }

    public function toOneSignal($notifiable)
    {
        $date = today()->translatedFormat('l, d F Y');
        $cuti = $this->paidLeave;
        return [
            'heading' => $cuti->kategori->name . " kedaluarsa. $date!",
            'body' => $cuti->kategori->name . " anda dengan subjek :\n{$cuti->title}\n\nTelah kedaluarsa. Status kehadiran anda akan kembali normal.",
            'user_id' => $notifiable->id
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
