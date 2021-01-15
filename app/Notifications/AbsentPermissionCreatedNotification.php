<?php

namespace App\Notifications;

use App\Models\AbsentPermission;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Notifications\Channels\OneSignalChannel;
use Carbon\Carbon;
use Illuminate\Notifications\Messages\MailMessage;

class AbsentPermissionCreatedNotification extends Notification
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
        $start_date = Carbon::parse($izin->start_date)->translatedFormat('l, d F Y');
        $due_date = Carbon::parse($izin->due_date)->translatedFormat('l, d F Y');
        return [
            'heading' => "Izin diajukan!",
            'body' => "Izin baru diajukan:\nJudul : $izin->title\nMulai : $start_date\nSampai : $due_date\nStatus :\nIzin diterima dan akan ditinjau kembali. Jika tidak sesuai ketentuan, maka izin akan dibatalkan.",
        ];
    }

    public function toOneSignal($notifiable)
    {
        $izin = $this->permission;
        $start_date = Carbon::parse($izin->start_date)->translatedFormat('l, d F Y');
        $due_date = Carbon::parse($izin->due_date)->translatedFormat('l, d F Y');
        $headings = "Izin diajukan!";
        $body = "Izin baru diajukan:\nJudul : $izin->title\nMulai : $start_date\nSampai : $due_date\nStatus :\nIzin diterima dan akan ditinjau kembali. Jika tidak sesuai ketentuan, maka izin akan dibatalkan.";
        sendNotification("Izin baru diajukan oleh  {$notifiable->name} :\nJudul : $izin->title\nMulai : $start_date\nSampai : $due_date", 'Pengajuan izin!', 2);

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
