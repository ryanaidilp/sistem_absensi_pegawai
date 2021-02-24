<?php

namespace App\Notifications;

use App\Models\Attende;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Notifications\Channels\OneSignalChannel;
use Illuminate\Notifications\Messages\MailMessage;

class AttendeStatusUpdatedNotification extends Notification
{
    use Queueable;

    private $attende;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Attende $attende)
    {
        $this->attende = $attende;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return [OneSignalChannel::class];
    }

    public function toOneSignal($notifiable)
    {
        $presence = $this->attende;
        $date = today()->translatedFormat('l, d F Y');
        return [
            'heading' => now()->translatedFormat('d/m/Y H:i:s') . " - Presensi berhasil!",
            'body' => "Presensi $date berhasil :\nJenis Presensi : {$presence->kode_absen->tipe->name}\nStatus Kehadiran : {$presence->status_kehadiran->name}\nJam Presensi : {$presence->attend_time->translatedFormat('H:i:s')}",
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
