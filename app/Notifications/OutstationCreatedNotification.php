<?php

namespace App\Notifications;

use App\Models\Outstation;
use App\Notifications\Channels\OneSignalChannel;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OutstationCreatedNotification extends Notification
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
        $start_date = Carbon::parse($outstation->start_date)->translatedFormat('l, d F Y');
        $due_date = Carbon::parse($outstation->due_date)->translatedFormat('l, d F Y');
        return [
            'heading' => "Dinas Luar diajukan!",
            'body' => "Dinas Luar baru diajukan:
            \nJudul : $outstation->title
            \nMulai : $start_date
            \nSampai : $due_date
            \nStatus : Menunggu Persetujuan",
        ];
    }

    public function toOneSignal($notifiable)
    {
        $outstation = $this->outstation;
        $start_date = Carbon::parse($outstation->start_date)->translatedFormat('l, d F Y');
        $due_date = Carbon::parse($outstation->due_date)->translatedFormat('l, d F Y');
        return [
            'heading' => "Dinas Luar diajukan!",
            'body' => "Dinas Luar baru diajukan:
            \nJudul : $outstation->title
            \nMulai : $start_date
            \nSampai : $due_date
            \nStatus : Menunggu Persetujuan",
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
