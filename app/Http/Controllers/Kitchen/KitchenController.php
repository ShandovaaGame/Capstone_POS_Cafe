<?php

namespace App\Http\Controllers\Kitchen;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class KitchenController extends Controller
{
    public function index()
    {
        $orders = Order::with(['items.menu.category', 'cafeTable'])
            ->whereIn('status', [Order::STATUS_PENDING, Order::STATUS_DIPROSES])
            ->whereDate('created_at', today())
            ->orderBy('created_at')
            ->limit(50)
            ->get();

        $ordersData = $orders->map(fn ($o) => [
            'id' => $o->id,
            'order_code' => $o->order_code,
            'status' => $o->status,
            'customer_name' => $o->customer_name,
            'table_number' => $o->cafeTable?->table_number,
            'created_at' => $o->created_at->toISOString(),
            'total_amount' => $o->total_amount,
            'items' => $o->items->map(fn ($i) => [
                'name' => $i->menu->name,
                'quantity' => $i->quantity,
                'category' => $i->menu->category?->name ?? '',
            ]),
        ]);

        return Inertia::render('Dapur/Index', [
            'orders' => $ordersData,
        ]);
    }

    public function riwayat()
    {
        $riwayatOrders = Order::with(['items.menu.category', 'cafeTable'])
            ->where('status', Order::STATUS_SELESAI)
            ->where('processed_by', Auth::id())
            ->whereDate('created_at', today())
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get()
            ->map(fn ($order) => [
                'id' => $order->id,
                'order_code' => $order->order_code,
                'status' => $order->status,
                'customer_name' => $order->customer_name,
                'table_number' => $order->cafeTable?->table_number,
                'created_at' => $order->created_at->toISOString(),
                'updated_at' => $order->updated_at->toISOString(),
                'total_amount' => $order->total_amount,
                'items' => $order->items->map(fn ($i) => [
                    'name' => $i->menu->name,
                    'quantity' => $i->quantity,
                    'category' => $i->menu->category?->name,
                ]),
            ]);

        return Inertia::render('Dapur/Riwayat', ['riwayatOrders' => $riwayatOrders]);
    }

    public function bump(Order $order)
    {
        $validTransitions = [
            Order::STATUS_PENDING => Order::STATUS_DIPROSES,
            Order::STATUS_DIPROSES => Order::STATUS_SELESAI,
        ];

        $nextStatus = $validTransitions[$order->status] ?? null;

        if (! $nextStatus) {
            return response()->json(['message' => 'Tidak ada transisi status yang valid.'], 409);
        }

        $order->update([
            'status' => $nextStatus,
            'processed_by' => Auth::id(),
        ]);

        return response()->json([
            'message' => 'Status berhasil diperbarui.',
            'order' => [
                'id' => $order->id,
                'status' => $order->status,
            ],
        ]);
    }
}
