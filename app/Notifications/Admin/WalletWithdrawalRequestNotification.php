<?php

namespace App\Notifications\Admin;

use App\Contracts\HasWallet;
use App\Enums\Queue;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WalletWithdrawalRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @param \App\Contracts\HasWallet&\Illuminate\Database\Eloquent\Model $user
     * @param float $amount
     * @return void
     */
    public function __construct(protected HasWallet&Model $user, protected float $amount)
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
            ->line("{$this->user->fullName} has requested for NGN {$this->amount} to be withdrawn from their wallet.")
            ->line('Kindly log onto your dashboard to review it!');
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
            'title' => 'Wallet Withdrawal Request',
            'body' =>
                "{$this->user->fullName} has requested for NGN {$this->amount} to be withdrawn from their wallet.",
        ];
    }
}
