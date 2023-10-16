<?php

namespace App\Notifications\User;

use App\Enums\KycAttribute;
use App\Enums\Queue;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class KycVerificationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @param \App\Enums\KycAttribute $type
     * @param bool $status
     * @return void
     */
    public function __construct(protected KycAttribute $type, protected bool $status)
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
        return ['mail', 'database'];
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
            ->line("Your {$this->type->name} verification " . ($this->status ? ' was successful.' : ' failed.'))
            ->line(
                $this->status
                    ? 'Thank you for using our application!'
                    : 'Kindly visit your dashboard to update it!'
            );
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
            'title' => 'KYC Verification Notification',
            'body' => "Your {$this->type->name} verification " . ($this->status ? ' was successful.' : ' failed.'),
        ];
    }
}
