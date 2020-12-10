<?php

namespace App\Notifications;

use App\Models\Outstation;
use App\Notifications\Channels\OneSignalChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OutstationRejectedNotification extends Notification
{
    use Queueable;

    private $outstation;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Outstation $outstation)
    {
        $this->outstation = $outstation;
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
        return [
            'heading' => "Dinas Luar ditolak!",
            'body' => "Dinas Luar $outstation->title anda telah ditolak pada : " . now()->translatedFormat('l, d F Y H:i:s'),
        ];
    }

    public function toOneSignal($notifiable)
    {
        $outstation = $this->outstation;
        return [
            'heading' => "Dinas Luar ditolak!",
            'body' => "Dinas Luar $outstation->title anda telah ditolak pada : " . now()->translatedFormat('l, d F Y H:i:s'),
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
