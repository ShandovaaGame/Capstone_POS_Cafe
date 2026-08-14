<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderQrisReviewed implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Order  $order,
        public readonly string $decision,
        public readonly ?string $reason = null,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('orders')];
    }

    public function broadcastAs(): string
    {
        return 'OrderQrisReviewed';
    }

    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->order->id,
            'decision' => $this->decision,
            'reason'   => $this->reason,
        ];
    }
}
