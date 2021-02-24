<?php

namespace App\Notifications;

use App\Models\PaidLeave;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Notifications\Channels\OneSignalChannel;
use Illuminate\Notifications\Messages\MailMessage;

class PaidLeaveRejectedNotification extends Notification
{
    use Queueable;

    private $paidLeave;
    private $reason;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(PaidLeave $paidLeave, $reason)
    {
        $this->paidLeave = $paidLeave;
        $this->reason = $reason;
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
        $reason = $this->reason;
        return [
            'heading' => $cuti->kategori->name . " ditolak!",
            'body' => $cuti->kategori->name . " anda dengan subjek :\n$cuti->title\n\nTelah ditolak pada :\n" . now()->translatedFormat('l, d F Y H:i:s') . "\n\nAlasan Penolakan :\n$reason",
        ];
    }

    public function toOneSignal($notifiable)
    {
        $cuti = $this->paidLeave;
        $reason = $this->reason;
        return [
            'heading' => now()->translatedFormat('d/m/Y H:i:s') . " - " . $cuti->kategori->name . " ditolak!",
            'body' => $cuti->kategori->name . " anda dengan subjek : $cuti->title telah ditolak.\n\nAlasan Penolakan :\n$reason",
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
