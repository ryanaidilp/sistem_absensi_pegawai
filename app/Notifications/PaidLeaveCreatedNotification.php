<?php

namespace App\Notifications;

use Carbon\Carbon;
use App\Models\PaidLeave;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Notifications\Channels\OneSignalChannel;
use Illuminate\Notifications\Messages\MailMessage;

class PaidLeaveCreatedNotification extends Notification
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
        $cuti = $this->paidLeave->load('kategori');
        $start_date = Carbon::parse($cuti->start_date)->translatedFormat('l, d F Y');
        $due_date = Carbon::parse($cuti->due_date)->translatedFormat('l, d F Y');
        return [
            'heading' => $cuti->kategori->name . ' diajukan!',
            'body' => "Cuti baru diajukan:\nJenis Cuti : {$cuti->kategori->name}\nJudul : $cuti->title\nMulai : $start_date\nSampai : $due_date\nStatus :\n{$cuti->kategori->name} diterima dan menunggu persetujuan."
        ];
    }

    public function toOneSignal($notifiable)
    {
        $cuti = $this->paidLeave->load('kategori');
        $start_date = Carbon::parse($cuti->start_date)->translatedFormat('l, d F Y');
        $due_date = Carbon::parse($cuti->due_date)->translatedFormat('l, d F Y');
        sendNotification($cuti->kategori->name . "diajukan oleh {$notifiable->name} :\n\nJudul : $cuti->title\nMulai : $start_date\nSampai : $due_date", "Pengajuan {$cuti->kategori->name}!", 2);
        return [
            'user_id' => $notifiable->id,
            'heading' => $cuti->kategori->name . ' diajukan!',
            'body' => "Cuti baru diajukan:\nJenis Cuti : {$cuti->kategori->name}\nJudul : $cuti->title\nMulai : $start_date\nSampai : $due_date\nStatus :\n{$cuti->kategori->name} diterima dan menunggu persetujuan."
        ];
    }
}
