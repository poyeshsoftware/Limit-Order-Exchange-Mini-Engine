<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderMatched implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly string $symbol,
        public readonly int $buyOrderId,
        public readonly int $sellOrderId,
        public readonly int $buyerId,
        public readonly int $sellerId,
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel|\Illuminate\Broadcasting\PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.'.$this->buyerId),
            new PrivateChannel('user.'.$this->sellerId),
        ];
    }

    public function broadcastWhen(): bool
    {
        if (config('broadcasting.default') !== 'pusher') {
            return false;
        }

        $pusherKey = trim((string) config('broadcasting.connections.pusher.key', ''));
        $pusherSecret = trim((string) config('broadcasting.connections.pusher.secret', ''));
        $pusherAppId = trim((string) config('broadcasting.connections.pusher.app_id', ''));

        if ($pusherKey === '' || $pusherKey === 'xxxx') {
            return false;
        }

        if ($pusherSecret === '' || $pusherSecret === 'xxxx') {
            return false;
        }

        if ($pusherAppId === '' || $pusherAppId === 'xxxx') {
            return false;
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'type' => 'matched',
            'symbol' => $this->symbol,
            'order_ids' => [
                'buy' => $this->buyOrderId,
                'sell' => $this->sellOrderId,
            ],
        ];
    }
}
