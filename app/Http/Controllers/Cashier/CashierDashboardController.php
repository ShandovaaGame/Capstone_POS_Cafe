<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Inertia\Inertia;

class CashierDashboardController extends Controller
{
    public function index()
    {
        $today = today();

        // Tanpa cache — dashboard dimuat ulang fresh tiap dibuka (reload-on-mount),
        // query difilter "hari ini" + index created_at sehingga tetap ringan.
        $stats = Order::whereDate('created_at', $today)
            ->selectRaw("
                SUM(CASE WHEN status = ? THEN total_amount ELSE 0 END) AS total_penjualan,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END)            AS jumlah_transaksi,
                SUM(CASE WHEN status = ? AND payment_method = 'cash'   THEN 1 ELSE 0 END) AS cash_pending,
                SUM(CASE WHEN status = ? AND payment_method = 'qris' AND payment_proof IS NOT NULL THEN 1 ELSE 0 END) AS qris_pending
            ", [
                Order::STATUS_SELESAI,
                Order::STATUS_SELESAI,
                Order::STATUS_PENDING,
                Order::STATUS_PENDING,
            ])
            ->first();

        $pesananAktif = Order::whereNotIn('status', [Order::STATUS_SELESAI, Order::STATUS_DIBATALKAN])
            ->where(fn($q) =>
                $q->where('order_type', 'cashier')
                  ->orWhere(fn($q2) =>
                      $q2->where('order_type', 'qr')
                         ->where(fn($q3) =>
                             $q3->where('payment_method', 'cash')
                                ->orWhere(fn($q4) => $q4->where('payment_method', 'qris')->whereNotNull('payment_proof'))
                         )
                  )
            )->count();

        $transaksiTerbaru = Order::with(['items' => fn($q) => $q->select('id', 'order_id', 'menu_id', 'quantity')->with(['menu' => fn($q) => $q->select('id', 'name')])])
            ->select('id', 'order_code', 'customer_name', 'total_amount', 'payment_method', 'status', 'created_at')
            ->whereDate('created_at', today())
            ->whereNotNull('payment_method')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn($o) => [
                'id'             => $o->id,
                'order_code'     => $o->order_code,
                'customer_name'  => $o->customer_name,
                'items_summary'  => $o->items->map(fn($i) => $i->quantity . 'x ' . $i->menu->name)->join(', '),
                'total_amount'   => $o->total_amount,
                'payment_method' => $o->payment_method,
                'status'         => $o->status,
            ]);

        return Inertia::render('Kasir/Dashboard', [
            'totalPenjualan'   => (float) ($stats->total_penjualan ?? 0),
            'jumlahTransaksi'  => (int)   ($stats->jumlah_transaksi ?? 0),
            'pesananAktif'     => $pesananAktif,
            'cashPending'      => (int)   ($stats->cash_pending ?? 0),
            'qrisPending'      => (int)   ($stats->qris_pending ?? 0),
            'transaksiTerbaru' => $transaksiTerbaru,
        ]);
    }
}
