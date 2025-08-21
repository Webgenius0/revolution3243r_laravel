<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;


class NewMessageNotification extends Notification
{
    use Queueable;

    protected $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    // Store in DB
    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'message_id' => $this->message->id,
            'room_id'    => $this->message->room_id,
            'sender_id'  => $this->message->sender_id,
            'text'       => $this->message->text,
            'file'       => $this->message->file,
        ];
    }
}
