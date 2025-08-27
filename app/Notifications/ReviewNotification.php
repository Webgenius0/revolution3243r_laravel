<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use App\Models\Review;

class ReviewNotification extends Notification
{
    use Queueable;

    protected $review;

    /**
     * Create a new notification instance.
     */
    public function __construct(Review $review)
    {
        $this->review = $review;
    }

    /**
     * Notification channels.
     */
    public function via($notifiable)
    {
        return ['database', 'broadcast', 'mail'];
    }

    /**
     * Email content.
     */
    public function toMail($notifiable)
    {
        $mail = (new MailMessage)
            ->subject('New Review Submitted')
            ->greeting('Hello Admin')
            ->line('A new review has been submitted.')
            ->line('User Name: ' . $this->review->users->name)
            ->line('Email: ' . $this->review->email)
            ->line('Message: ' . $this->review->message)
            ->line('Type: ' . $this->review->type);

        // Attach user avatar if exists
        // if ($this->review->users->avatar) {
        //     $mail->attach(($this->review->users->avatar));
        // }

        return $mail->line('Thank you for using our application!');
    }
    /**
     * Database notification.
     */
    public function toArray($notifiable)
    {

        return [
            'review_id' => $this->review->id,
            'user_id'   => $this->review->user_id,
            'email'     => $this->review->email,
            'message'   => $this->review->message,
            'type'      => $this->review->type,
        ];
    }

    /**
     * Broadcast notification (real-time).
     */

    /**
     * Broadcast channel for this notification.
     */
    public function broadcastOn()
    {
        return ['reviews.admin']; // Admins will listen to this channel
    }

    /**
     * Custom broadcast event name.
     */
    public function broadcastType()
    {
        return 'review.submitted'; // Custom event name for Laravel Echo
    }
}
