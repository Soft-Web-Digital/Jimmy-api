<?php

namespace App\Notifications\Auth;

use App\Enums\Queue;
use App\Models\TwoFaVerificationCode;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class TwofaNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @param string $code
     * @param string $ipAddress
     * @return void
     */
    public function __construct(private readonly string $code, private readonly string $ipAddress)
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
        $minutes = TwoFaVerificationCode::EXPIRATION_TIME_IN_MINUTES;

        return (new MailMessage())
            ->subject('Complete Your Authentication')
            ->line('An attempt was made to log into your account')
            ->line(new HtmlString(
                "IP Address: <b>{$this->ipAddress}</b>"
            ))
            ->line('Please use the code below to complete your authentication')
            ->line(new HtmlString(
                "<h1 style='text-align: center;'>{$this->code}</h1>"
            ))
            ->line("This authentication code will expire in {$minutes} " . str('minute')->plural($minutes) . '.')
            ->line('If you did not request an authentication code, you should reset your password.');
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
            'title' => 'Complete Your Authentication',
            'body' => 'Authentication code sent to your email',
        ];
    }
}
