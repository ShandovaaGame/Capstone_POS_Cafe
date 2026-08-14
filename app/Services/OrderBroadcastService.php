<?php

namespace App\Services;

use App\Events\OrderStatusUpdated;
use App\Models\Order;

class OrderBroadcastService
{
    public static function broadcastPendingCount(): void
    {
        $count = Order::cashierPendingCount();
        broadcast(new OrderStatusUpdated('pending', $count));
    }
}
