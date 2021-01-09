<?php

namespace App\Notifications;

use App\Models\Attende;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Notifications\Channels\OneSignalChannel;
use Illuminate\Notifications\Messages\MailMessage;

class AttendanceCanceledNotification extends Notification
{
    use Queueable;

    private $attende;
    private $reason;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Attende $attende, $reason)
    {
        $this->attende = $attende;
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
        $presence = $this->attende;
        $date = $presence->created_at->translatedFormat('l, d F Y');
        $type = $presence->kode_absen->tipe->name;
        $today = today()->translatedFormat('l, d F Y');
        return [
            'heading' => "Presensi dibatalkan, $today!",
            'body' => "Presensi anda dibatalkan :\nJenis Presensi : $type\nHari/Tanggal : $date\n\nAlasan Pembatalan : {$this->reason}",
        ];
    }

    public function toOneSignal($notifiable)
    {
        $presence = $this->attende;
        $date = $presence->created_at->translatedFormat('l, d F Y');
        $type = $presence->kode_absen->tipe->name;
        $today = today()->translatedFormat('l, d F Y');
        return [
            'heading' => "Presensi dibatalkan, $today!",
            'body' => "Presensi anda dibatalkan :\nJenis Presensi : $type\nHari/Tanggal : $date\n\nAlasan Pembatalan : {$this->reason}",
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
