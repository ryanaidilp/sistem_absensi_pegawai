<?php

namespace App\Notifications;

use App\Models\Outstation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Notifications\Channels\OneSignalChannel;
use Illuminate\Notifications\Messages\MailMessage;

class OutstationRejectedNotification extends Notification
{
    use Queueable;

    private $outstation;
    private $reason;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Outstation $outstation, $reason)
    {
        $this->outstation = $outstation;
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
        $outstation = $this->outstation;
        $reason = $this->reason;
        $body = "$outstation->title anda telah ditolak pada :\n" . now()->translatedFormat('l, d F Y H:i:s') . "\n\nAlasan penolakan:\n$reason";
        return [
            'heading' => "Dinas Luar ditolak!",
            'body' => $body
        ];
    }

    public function toOneSignal($notifiable)
    {
        $outstation = $this->outstation;
        $reason = $this->reason;
        $body = "Dinas Luar anda dengan subjek : $outstation->title telah ditolak.\n\nAlasan penolakan :\n$reason";
        return [
            'heading' => now()->translatedFormat('d/m/Y H:i:s') . " - Dinas Luar ditolak!",
            'body' => $body,
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
