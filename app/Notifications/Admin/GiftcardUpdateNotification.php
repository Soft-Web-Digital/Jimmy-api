<?php

namespace App\Notifications\Admin;

use App\Enums\Queue;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Kutia\Larafirebase\Messages\FirebaseMessage;

class GiftcardUpdateNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @param string $subject
     * @param string $body
     * @return void
     */
    public function __construct(public readonly string $subject, public readonly string $body)
    {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array<int, string>
     */
    public function via($notifiable)
    {
        return ['mail', 'database', 'firebase'];
    }

    /**
     * Determine which queues should be used for each notification channel.
     *
     * @return array<string, string>
     */
    public function viaQueues()
    {
        return [
            'mail' => Queue::MAIL->value,
            'database' => Queue::MAIL->value,
            'firebase' => Queue::MAIL->value,
        ];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage())
            ->greeting("Hello {$notifiable['firstname']}!")
            ->subject($this->subject)
            ->line($this->body)
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array<string, string>
     */
    public function toArray($notifiable)
    {
        return [
            'title' => $this->subject,
            'body' => $this->body,
        ];
    }

    /**
     * Get the firebase representation of the notification.
     * @param mixed $notifiable
     * @return mixed
     */
    public function toFirebase(mixed $notifiable)
    {
        $deviceTokens = is_array($notifiable->fcm_tokens) ? $notifiable->fcm_tokens : [$notifiable->fcm_tokens];

        return (new FirebaseMessage())
            ->withTitle($this->subject)
            ->withBody($this->body)
            ->asNotification($deviceTokens);
    }
}
