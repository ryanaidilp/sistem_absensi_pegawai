<?php

namespace App\Notifications;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Notifications\Channels\OneSignalChannel;
use Illuminate\Notifications\Messages\MailMessage;

class BirthdayNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
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

    /**
     * Save notification to OneSignal
     *
     * @param  mixed  $notifiable
     * @return array
     */

    public function toOneSignal($notifiable)
    {
        $dob = Carbon::parse($notifiable->date_of_birth);
        return [
            'heading' => "Selamat Ulang Tahun!",
            'body' => "Selamat ulang tahun yang ke {$dob->age} tahun. Semoga segala niat baik di tahun ini bisa segera tercapai.",
            'user_id' => $notifiable->id
        ];
    }
}
