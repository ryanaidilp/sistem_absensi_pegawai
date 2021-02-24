<?php

namespace App\Notifications;

use App\Models\Outstation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Notifications\Channels\OneSignalChannel;
use Illuminate\Notifications\Messages\MailMessage;

class OutstationApprovedNotification extends Notification
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
            'heading' => 'Dinas Luar disetujui!',
            'body' => "$outstation->title anda telah disetujui pada :\n" . now()->translatedFormat('l, d F Y H:i:s')
        ];
    }

    public function toOneSignal($notifiable)
    {
        $outstation = $this->outstation;
        return [
            'heading' => now()->translatedFormat('d/m/Y H:i:s') . ' - Dinas Luar disetujui!',
            'body' => "Dinas Luar anda dengan subjek : $outstation->title telah disetujui.",
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
