<?php

namespace App\Notifications\User;

use App\Enums\AssetTransactionTradeType;
use App\Enums\Queue;
use App\Models\AssetTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Kutia\Larafirebase\Messages\FirebaseMessage;

class AssetTransactionApprovedNotification extends Notification implements ShouldQueue
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
            ->subject(Str::headline("$ {$this->transaction->asset_amount} Asset {$this->transaction->trade_type->value} transaction completed"))
            ->greeting("Dear {$this->transaction->user?->firstname},")
            ->lineIf($this->transaction->trade_type == AssetTransactionTradeType::SELL, new HtmlString(
                "Your asset sell transaction of ($ {$this->transaction->asset_amount}) has been completed "
                . 'successfully and the Naira equivalent has been credited into your account.'
            ))
            ->lineIf($this->transaction->trade_type == AssetTransactionTradeType::BUY, new HtmlString(
                "Your asset buy transaction of ($ {$this->transaction->asset_amount}) has been completed "
                . 'successfully and the asset equivalent has been sent into your wallet.'
            ))
            ->line('Please find a full summary of your transaction below;')
            ->lineif($this->transaction->trade_type == AssetTransactionTradeType::SELL, new HtmlString(
                'Transaction details: <br> ' .
                "Transaction ID: {$this->transaction->reference} <br>" .
                "Asset name: {$this->transaction->asset->name} ({$this->transaction->network->name}) <br>" .
                "Rate: {$this->transaction->rate} <br>" .
                "Service charge: {$this->transaction->service_charge} <br>" .
                "Asset amount: $ {$this->transaction->asset_amount} <br>" .
                'Amount in Naira: NGN ' . number_format($this->transaction->payable_amount, 2) . ' <br><br>' .
                'Bank details: <br>' .
                "Bank name: {$this->transaction->bank->name} <br>" .
                "Account number: {$this->transaction->account_number} <br>" .
                "Account name: {$this->transaction->account_name}"
            ))
            ->lineif($this->transaction->trade_type == AssetTransactionTradeType::BUY, new HtmlString(
                'Transaction details: <br> ' .
                "Transaction ID: {$this->transaction->reference} <br>" .
                "Asset name: {$this->transaction->asset->name} ({$this->transaction->network->name}) <br>" .
                "Rate: {$this->transaction->rate} <br>" .
                "Service charge: {$this->transaction->service_charge} <br>" .
                "Asset amount: $ {$this->transaction->asset_amount} <br>" .
                'Amount in Naira: NGN ' . number_format($this->transaction->payable_amount, 2) . ' <br><br>' .
                "Wallet Address: {$this->transaction->wallet_address}"
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
            'title' => Str::headline("Asset {$this->transaction->trade_type->value} transaction completed"),
            'body' => "Your asset {$this->transaction->trade_type->value} transaction "
                . "with reference {$this->transaction->reference} has been completed.",
        ];
    }

    /**
     * Get the firebase representation of the notification.
     * @param mixed $notifiable
     * @return mixed
     */
    public function toFirebase(mixed $notifiable): mixed
    {
        $deviceTokens = is_array($notifiable->fcm_tokens) ? $notifiable->fcm_tokens : [$notifiable->fcm_tokens];

        return (new FirebaseMessage())
            ->withTitle(Str::headline("Asset {$this->transaction->trade_type->value} transaction completed"))
            ->withBody(
                "Your asset {$this->transaction->trade_type->value} transaction "
                . "with reference {$this->transaction->reference} has been completed."
            )
            ->asNotification($deviceTokens);
    }
}
