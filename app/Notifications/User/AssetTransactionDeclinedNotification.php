<?php

namespace App\Notifications\User;

use App\Enums\Queue;
use App\Models\AssetTransaction;
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
     * @param \App\Models\AssetTransaction $transaction
     * @return void
     */
    public function __construct(private readonly AssetTransaction $transaction)
    {
        //
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
            ->subject(Str::headline("$ {$this->transaction->asset_amount} Asset {$this->transaction->trade_type->value} transaction declined"))
            ->greeting("Dear {$this->transaction->user?->firstname},")
            ->line(
                "Your asset buy transaction of ($ {$this->transaction->asset_amount}) for " .
                    "({$this->transaction->asset->name}) has been declined by the admin."
            )
            ->line('Please contact support for more information or complaints.')
            ->lineIf(!is_null($this->transaction->review_note), new HtmlString(
                'Why is my transaction declined? <br>' .
                "Reason: <i>{$this->transaction->review_note}</i>"
            ));
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
            'title' => Str::headline("Asset {$this->transaction->trade_type->value} transaction declined"),
            'body' => "Your asset {$this->transaction->trade_type->value} transaction "
                . "with reference {$this->transaction->reference} has been declined.",
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
            ->withTitle(Str::headline("Asset {$this->transaction->trade_type->value} transaction declined"))
            ->withBody(
                "Your asset {$this->transaction->trade_type->value} transaction "
                . "with reference {$this->transaction->reference} has been declined."
            )
            ->asNotification($deviceTokens);
    }
}
