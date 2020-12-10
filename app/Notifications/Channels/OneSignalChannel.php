<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;

class OneSignalChannel
{
    public function send($notifiable, Notification $notification)
    {
        $message = $notification->toOneSignal($notifiable);
        if (isset($message['user_id'])) {
            sendNotification($message['body'], $message['heading'], $message['user_id']);
        } else {
            sendNotification($message['body'], $message['heading']);
        }
    }
}
