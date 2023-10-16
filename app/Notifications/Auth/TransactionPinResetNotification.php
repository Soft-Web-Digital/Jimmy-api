<?php

namespace App\Notifications\Auth;

use App\Enums\Queue;
use App\Models\TransactionPinResetCode;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class TransactionPinResetNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @param string $code
     * @return void
     */
    public function __construct(private readonly string $code)
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
        $minutes = TransactionPinResetCode::EXPIRATION_TIME_IN_MINUTES;

        return (new MailMessage())
            ->subject('Reset Transaction Pin')
            ->line('Please use the code below to reset your transaction pin')
            ->line(new HtmlString(
                "<h1 style='text-align: center;'>{$this->code}</h1>"
            ))
            ->line("This reset code will expire in {$minutes} " . str('minute')->plural($minutes) . '.')
            ->line('If you did not request a transaction pin reset, no further action is required.');
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
            'title' => 'Reset Transaction Pin',
            'body' => 'Reset code sent to your email',
        ];
    }
}
