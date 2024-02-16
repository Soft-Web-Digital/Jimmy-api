<?php

namespace App\Notifications\User;

use App\Enums\GiftcardTradeType;
use App\Enums\Queue;
use App\Models\Giftcard;
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
     * @param \App\Models\Giftcard $transaction
     * @return void
     */
    public function __construct(private readonly Giftcard $transaction)
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
            ->subject(Str::headline("$ {$this->transaction->amount} Giftcard {$this->transaction->trade_type->value} transaction completed"))
            ->greeting("Dear {$this->transaction->user?->firstname},")
            ->lineIf($this->transaction->trade_type == GiftcardTradeType::SELL, new HtmlString(
                "Your giftcard sale of ($ {$this->transaction->amount}) has been completed "
                . 'successfully and the Naira equivalent has been credited into your account.'
            ))
            ->line('Please find a full summary of your transaction below;')
            ->lineif($this->transaction->trade_type == GiftcardTradeType::SELL, new HtmlString(
                'Transaction details: <br> ' .
                "Transaction ID: {$this->transaction->reference} <br>" .
                "Asset name: {$this->transaction->giftcardProduct->giftcardCategory->name} ({$this->transaction->giftcardProduct->name}) <br>" .
                "Rate: {$this->transaction->rate} <br>" .
                "Service charge: {$this->transaction->service_charge} <br>" .
                "Asset amount: $ {$this->transaction->amount} <br>" .
                'Amount in Naira: NGN ' . number_format($this->transaction->payable_amount, 2) . ' <br><br>' .
                'Bank details: <br>' .
                "Bank name: {$this->transaction->bank->name} <br>" .
                "Account number: {$this->transaction->account_number} <br>" .
                "Account name: {$this->transaction->account_name}"
            ))
            ->line('Thank you for choosing Jimmy Xchange.');
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
            'title' => Str::headline("Giftcard {$this->transaction->trade_type->value} transaction approved"),
            'body' => "Your giftcard {$this->transaction->trade_type->value} transaction "
                . "with reference {$this->transaction->reference} has been approved.",
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
            ->withTitle(Str::headline("Giftcard {$this->transaction->trade_type->value} transaction approved"))
            ->withBody(
                "Your giftcard {$this->transaction->trade_type->value} transaction "
                . "with reference {$this->transaction->reference} has been approved."
            )
            ->asNotification($deviceTokens);
    }
}
