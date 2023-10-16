<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GiftcardCreated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @var array<string, mixed>
     */
    public array $giftcard;

    /**
     * Create a new event instance.
     *
     * @param array<string, mixed> $giftcard
     * @return void
     */
    public function __construct(array $giftcard)
    {
        $this->giftcard = $giftcard;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel
     */
    public function broadcastOn(): Channel
    {
        return new Channel('giftcards');
    }

    /**
     * Get the name the event should broadcast as.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'new-giftcard';
    }
}
