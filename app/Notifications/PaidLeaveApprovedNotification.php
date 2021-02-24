<?php

namespace App\Notifications;

use App\Models\PaidLeave;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Notifications\Channels\OneSignalChannel;
use Illuminate\Notifications\Messages\MailMessage;

class PaidLeaveApprovedNotification extends Notification
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
        $cuti = $this->paidLeave;
        return [
            'heading' => $cuti->kategori->name . ' disetujui!',
            'body' => "{$cuti->kategori->name} anda dengan subjek\n$cuti->title\n\nTelah disetujui pada :\n" . now()->translatedFormat('l, d F Y H:i:s')
        ];
    }

    public function toOneSignal($notifiable)
    {
        $cuti = $this->paidLeave;
        return [
            'heading' => now()->translatedFormat('d/m/Y H:i:s') . " - " . $cuti->kategori->name . ' disetujui!',
            'body' => "{$cuti->kategori->name} anda dengan subjek : $cuti->title telah disetujui.",
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
