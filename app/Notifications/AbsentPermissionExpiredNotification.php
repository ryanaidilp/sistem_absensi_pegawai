<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use App\Models\AbsentPermission;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Notifications\Channels\OneSignalChannel;
use Illuminate\Notifications\Messages\MailMessage;

class AbsentPermissionExpiredNotification extends Notification
{
    use Queueable;

    private $permission;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(AbsentPermission $permission)
    {
        $this->permission = $permission;
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
        $izin = $this->permission;;
        return [
            'heading' => "Izin kedaluarsa. $date",
            'body' => "Izin anda dengan subjek izin :\n{$izin->title}\nTelah kedaluarsa. Status kehadiran anda akan kembali normal."
        ];
    }

    public function toOneSignal($notifiable)
    {
        $date = today()->translatedFormat('l, d F Y');
        $izin = $this->permission;
        $headings = now()->translatedFormat('d/m/Y H:i:s') . " - Izin kedaluarsa!";
        $body = "Izin anda dengan subjek izin :\n{$izin->title}\nTelah kedaluarsa. Status kehadiran anda akan kembali normal.";
        return [
            'heading' => $headings,
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
