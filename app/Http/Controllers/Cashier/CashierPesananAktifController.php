<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Inertia\Inertia;

class CashierPesananAktifController extends Controller
{
    public function index()
    {
        $orders = Order::with(['items.menu', 'cafeTable', 'cashier'])
            ->whereNotIn('status', [Order::STATUS_SELESAI, Order::STATUS_DIBATALKAN])
            ->where(function ($q) {
                // Order dari kasir: selalu tampil
                $q->where('order_type', 'cashier')
                  // Order dari pelanggan via QR:
                    ->orWhere(fn ($q2) => $q2->where('order_type', 'qr')
                        ->where(fn ($q3) =>
                            // Cash: tampil begitu dipilih
                            $q3->where('payment_method', 'cash')
                               // QRIS: tampil saat bukti dikirim (pending) ATAU sudah dikonfirmasi (diproses, proof dihapus)
                                ->orWhere(fn ($q4) => $q4->where('payment_method', 'qris')
                                    ->where(fn ($q5) => $q5->whereNotNull('payment_proof')
                                        ->orWhere('status', Order::STATUS_DIPROSES)
                                    )
                                )
                        )
                    );
            })
            ->latest()
            ->get();

        $counts = [
            'all' => $orders->count(),
            'pending' => $orders->where('status', Order::STATUS_PENDING)->count(),
            'diproses' => $orders->where('status', Order::STATUS_DIPROSES)->count(),
            'belum_bayar' => $orders->where('payment_method', 'bayar_nanti')->count(),
        ];

        $ordersData = $orders->map(fn ($o) => [
            'id' => $o->id,
            'order_code' => $o->order_code,
            'status' => $o->status,
            'payment_method' => $o->payment_method,
            'customer_name' => $o->customer_name,
            'table_number' => $o->cafeTable?->table_number,
            'created_at' => $o->created_at->toISOString(),
            'items_summary' => $o->items->map(fn ($i) => $i->quantity.'x '.$i->menu->name)->join(', '),
            'total_amount' => $o->total_amount,
            'payment_proof' => $o->payment_proof ? asset('storage/'.$o->payment_proof) : null,
            'rejection_note' => $o->rejection_note,
            'items' => $o->items->map(fn ($i) => [
                'name' => $i->menu->name,
                'quantity' => $i->quantity,
                'unit_price' => $i->unit_price,
                'subtotal' => $i->subtotal,
            ]),
        ]);

        return Inertia::render('Kasir/PesananAktif', [
            'orders' => $ordersData,
            'counts' => $counts,
        ]);
    }
}
