<?php

namespace App\Notifications\User;

use App\Enums\AssetTransactionTradeType;
use App\Enums\Queue;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Kutia\Larafirebase\Messages\FirebaseMessage;

class AssetTransactionDeclinedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @param \App\Enums\AssetTransactionTradeType $tradeType
     * @param string $reference
     * @param string|null $reviewNote
     * @param array<int, string>|null|string $reviewProof
     * @return void
     */
    public function __construct(
        private readonly AssetTransactionTradeType $tradeType,
        private readonly string $reference,
        private readonly string|null $reviewNote = null,
        private readonly array|null|string $reviewProof = null
    ) {
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
            'firebase' => Queue::MAIL->value
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
            ->subject(Str::headline("Asset {$this->tradeType->value} transaction declined"))
            ->line(new HtmlString(
                "This is to notify you that your asset {$this->tradeType->value} transaction "
                . "with reference <b>{$this->reference}</b> has been declined."
            ))
            ->lineIf(!is_null($this->reviewNote), new HtmlString("Reason: <i>{$this->reviewNote}</i>"))
            ->when(
                $this->reviewProof,
                function (MailMessage $mailMessage) {
                    foreach ($this->reviewProof as $proof) {
                        $mailMessage->attach($proof);
                    }
                }
            )
            ->line('Please visit your dashboard to learn more.');
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
            'title' => Str::headline("Asset {$this->tradeType->value} transaction declined"),
            'body' => "Your asset {$this->tradeType->value} transaction "
                . "with reference {$this->reference} has been declined.",
        ];
    }

    /**
     * Get the firebase representation of the notification.
     *
     * @param mixed $notifiable
     * @return mixed
     */
    public function toFirebase(mixed $notifiable): mixed
    {
        $deviceTokens = is_array($notifiable->fcm_tokens) ? $notifiable->fcm_tokens : [$notifiable->fcm_tokens];

        return (new FirebaseMessage())
            ->withTitle(Str::headline("Asset {$this->tradeType->value} transaction declined"))
            ->withBody(
                "Your asset {$this->tradeType->value} transaction "
                . "with reference {$this->reference} has been declined."
            )
            ->asNotification($deviceTokens);
    }
}
