<?php

namespace App\Notifications;

use App\Models\AbsentPermission;
use App\Notifications\Channels\OneSignalChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class AbsentPermissionRejectedNotification extends Notification
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
        $izin = $this->permission;
        return [
            'heading' => "Izin ditolak!",
            'body' => "Izin $izin->title anda telah ditolak pada : " . now()->translatedFormat('l, d F Y H:i:s'),
        ];
    }

    public function toOneSignal($notifiable)
    {
        $izin = $this->permission;
        return [
            'heading' => "Izin ditolak!",
            'body' => "Izin $izin->title anda telah ditolak pada : " . now()->translatedFormat('l, d F Y H:i:s'),
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
