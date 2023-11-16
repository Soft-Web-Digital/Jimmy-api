<?php

namespace App\Notifications\User;

use App\Enums\GiftcardTradeType;
use App\Enums\Queue;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Kutia\Larafirebase\Messages\FirebaseMessage;

class GiftcardApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @param \App\Enums\GiftcardTradeType $tradeType
     * @param string $reference
     * @param string|null $reviewNote
     * @param array<int, string>|null|string $reviewProof
     * @return void
     */
    public function __construct(
        private readonly GiftcardTradeType $tradeType,
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
            ->subject(Str::headline("Giftcard {$this->tradeType->value} transaction approved"))
            ->line(new HtmlString(
                "This is to notify you that your giftcard {$this->tradeType->value} transaction "
                . "with reference <b>{$this->reference}</b> has been approved."
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
            'title' => Str::headline("Giftcard {$this->tradeType->value} transaction approved"),
            'body' => "Your giftcard {$this->tradeType->value} transaction "
                . "with reference {$this->reference} has been approved.",
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
            ->withTitle(Str::headline("Giftcard {$this->tradeType->value} transaction approved"))
            ->withBody(
                "Your giftcard {$this->tradeType->value} transaction "
                . "with reference {$this->reference} has been approved."
            )
            ->asNotification($deviceTokens);
    }
}
