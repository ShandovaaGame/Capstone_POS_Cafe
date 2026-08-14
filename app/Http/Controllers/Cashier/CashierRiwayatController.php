<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CashierRiwayatController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::with(['cashier' => fn ($q) => $q->select('id', 'name')])
            ->select('id', 'order_code', 'cashier_id', 'customer_name', 'total_amount', 'payment_method', 'status', 'created_at')
            ->whereIn('status', [Order::STATUS_SELESAI, Order::STATUS_DIBATALKAN])
            ->when($request->search, fn ($q) => $q->where('order_code', 'like', '%'.$request->search.'%')
                ->orWhere('customer_name', 'like', '%'.$request->search.'%'))
            ->when($request->date, fn ($q) => $q->whereDate('created_at', $request->date))
            ->when($request->input('method'), fn ($q) => $q->where('payment_method', $request->input('method')))
            ->latest()
            ->paginate(25)
            ->through(fn ($o) => [
                'id' => $o->id,
                'order_code' => $o->order_code,
                'created_at' => $o->created_at->toISOString(),
                'total_amount' => $o->total_amount,
                'payment_method' => $o->payment_method,
                'cashier_name' => $o->cashier?->name,
                'customer_name' => $o->customer_name,
                'status' => $o->status,
            ]);

        return Inertia::render('Kasir/RiwayatPesanan', [
            'orders' => $orders,
            'filters' => $request->only(['search', 'date', 'method']),
        ]);
    }
}
