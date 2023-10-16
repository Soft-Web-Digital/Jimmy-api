<?php

declare(strict_types=1);

namespace App\Services\Alert;

use App\Enums\AlertChannel;
use App\Mail\AlertDispatched;
use App\Models\Alert;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Kutia\Larafirebase\Facades\Larafirebase;

class AlertDispatchService
{
    /**
     * Dispatch the alert.
     *
     * @param \App\Models\Alert $alert
     * @return void
     */
    public function dispatch(Alert $alert): void
    {
        DB::beginTransaction();

        try {
            $recipients = $alert->recipients();

            /** @var array<int, \App\Enums\AlertChannel> $channels */
            $channels = $alert->channels;

            if (in_array(AlertChannel::EMAIL->value, $channels)) {
                $this->sendEmailAlert($recipients, $alert);
            }

            if (in_array(AlertChannel::IN_APP->value, $channels)) {
                $this->sendInAppAlert($recipients, $alert);
            }

            if (in_array(AlertChannel::PUSH->value, $channels)) {
                $this->sendPushAlert($recipients, $alert);
            }

            $alert->markAsSuccessful(count($recipients));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            $alert->markAsFailed($e->getMessage());
        }
    }

    /**
     * Send alert to email channel.
     *
     * @param \Illuminate\Support\Collection<int, \Illuminate\Database\Eloquent\Model> $recipients
     * @param \App\Models\Alert $alert
     * @return void
     */
    private function sendEmailAlert(Collection $recipients, Alert $alert): void
    {
        foreach ($recipients as $recipient) {
            $this->sendMailNotification([$recipient], $alert);
        }
    }

    /**
     * Send alert to in_app channel.
     *
     * @param \Illuminate\Support\Collection<int, \Illuminate\Database\Eloquent\Model> $recipients
     * @param \App\Models\Alert $alert
     * @return void
     */
    private function sendInAppAlert(Collection $recipients, Alert $alert): void
    {
        $notifications = [];

        $now = now();
        foreach ($recipients as $recipient) {
            $notifications[] = [
                'id' => Str::orderedUuid(),
                'type' => get_class($alert),
                'notifiable_type' => $recipient->getMorphClass(),
                'notifiable_id' => $recipient->getKey(),
                'data' => json_encode([
                    'title' => $alert->title,
                    'body' => $alert->body,
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table((new DatabaseNotification())->getTable())->insert($notifications);
    }

    /**
     * Send alert to push channel.
     *
     * @param \Illuminate\Support\Collection<int, \Illuminate\Database\Eloquent\Model> $recipients
     * @param \App\Models\Alert $alert
     * @return void
     */
    private function sendPushAlert(Collection $recipients, Alert $alert): void
    {
        $deviceTokens = [];

        foreach ($recipients as $recipient) {
            $deviceTokens[] = $recipient->fcm_tokens ?? null;

            if (count($deviceTokens) >= 500) {
                $this->sendPushNotification($deviceTokens, $alert);
                $deviceTokens = [];
            }
        }

        if (count($deviceTokens) > 0) {
            $this->sendPushNotification($deviceTokens, $alert);
        }
    }

    /**
     * Send mail notification.
     *
     * @param array $recipients
     * @param Alert $alert
     * @return void
     */
    private function sendMailNotification(array $recipients, Alert $alert): void  // @phpstan-ignore-line
    {
        Mail::to($recipients)->send(new AlertDispatched($alert->title, $alert->body));
    }

    /**
     * Send push notification.
     *
     * @param array $tokens
     * @param Alert $alert
     * @return void
     */
    private function sendPushNotification(array $tokens, Alert $alert): void  // @phpstan-ignore-line
    {
        // Firebase notifications
        $deviceTokens = collect($tokens)->collapse()->unique();
        if ($deviceTokens->isNotEmpty()) {
            Larafirebase::withTitle($alert->title)
                ->withBody($alert->body)
                ->sendNotification($deviceTokens->toArray());
        }
    }
}
