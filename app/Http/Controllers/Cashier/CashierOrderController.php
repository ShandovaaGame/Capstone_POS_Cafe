<?php

namespace App\Http\Controllers\Cashier;

use App\Events\OrderQrisReviewed;
use App\Http\Controllers\Controller;
use App\Jobs\BroadcastPendingCount;
use App\Models\Order;
use App\Services\InventoryService;
use App\Services\WhatsAppReceiptService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CashierOrderController extends Controller
{
    public function show(Order $order)
    {
        $order->load(['items.menu', 'cafeTable', 'cashier']);

        return Inertia::render('Kasir/Order/Show', [
            'order' => [
                'id' => $order->id,
                'order_code' => $order->order_code,
                'status' => $order->status,
                'total_amount' => $order->total_amount,
                'customer_name' => $order->customer_name,
                'customer_phone' => $order->customer_phone,
                'payment_method' => $order->payment_method,
                'payment_proof' => $order->payment_proof,
                'rejection_note' => $order->rejection_note,
                'created_at' => $order->created_at->toISOString(),
                'cashier_name' => $order->cashier?->name,
                'table_number' => $order->cafeTable?->table_number,
                'items' => $order->items->map(fn ($i) => [
                    'id' => $i->id,
                    'name' => $i->menu->name,
                    'unit_price' => $i->unit_price,
                    'quantity' => $i->quantity,
                    'subtotal' => $i->subtotal,
                ]),
            ],
        ]);
    }

    public function cancel(Request $request, Order $order)
    {
        if (in_array($order->status, [Order::STATUS_SELESAI, Order::STATUS_DIBATALKAN])) {
            return response()->json(['message' => 'Pesanan ini tidak dapat dibatalkan.'], 409);
        }

        $request->validate(['reason' => 'nullable|string|max:255']);

        $order->update([
            'status'         => Order::STATUS_DIBATALKAN,
            'rejection_note' => $request->reason,
            'cashier_id'     => Auth::id(),
            'cancelled_at'   => now(),
        ]);

        BroadcastPendingCount::dispatch();

        return response()->json(['message' => 'Pesanan dibatalkan.']);
    }

    public function updateStatus(Request $request, Order $order, InventoryService $inventoryService)
    {
        $request->validate(['status' => 'required|string|in:diproses,selesai']);

        $validTransitions = [
            Order::STATUS_PENDING => Order::STATUS_DIPROSES,
            Order::STATUS_DIPROSES => Order::STATUS_SELESAI,
        ];

        $allowed = $validTransitions[$order->status] ?? null;
        if (! $allowed || $allowed !== $request->status) {
            return response()->json(['message' => 'Transisi status tidak valid.'], 409);
        }

        // Blok selesai jika bayar_nanti
        if ($request->status === Order::STATUS_SELESAI && $order->payment_method === 'bayar_nanti') {
            return response()->json(['message' => 'Pesanan belum lunas. Konfirmasi pembayaran terlebih dahulu.'], 409);
        }

        if ($request->status === Order::STATUS_DIPROSES) {
            $order->load('items.menu');
            $items = $order->items->map(fn($i) => ['menu_id' => $i->menu_id, 'quantity' => $i->quantity])->toArray();
            $fulfillment = $inventoryService->canFulfillOrder($items);

            if (! $fulfillment['can_fulfill']) {
                $first = $fulfillment['insufficient_ingredients'][0];
                $name = $first['ingredient_name'] ?? $first['menu_name'] ?? 'item';
                return response()->json(['message' => "Stok '{$name}' tidak mencukupi."], 409);
            }
        }

        try {
            DB::transaction(function () use ($request, $order, $inventoryService) {
                $data = ['status' => $request->status, 'cashier_id' => Auth::id()];

                if ($request->status === Order::STATUS_DIPROSES) {
                    $data['processed_at'] = now();
                } elseif ($request->status === Order::STATUS_SELESAI) {
                    $data['completed_at'] = now();
                }

                $order->update($data);

                if ($request->status === Order::STATUS_DIPROSES) {
                    $inventoryService->processSaleForOrder($order, Auth::id());
                }
            });
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal memproses pesanan: '.$e->getMessage()], 500);
        }

        BroadcastPendingCount::dispatch();

        return response()->json(['message' => 'Status diperbarui.']);
    }

    public function confirmPayment(Request $request, Order $order)
    {
        if ($order->payment_method !== 'bayar_nanti') {
            return response()->json(['message' => 'Sudah lunas.'], 409);
        }
        $request->validate(['payment_method' => 'required|in:cash,qris']);

        $order->update([
            'payment_method' => $request->payment_method,
            'cashier_id' => Auth::id(),
        ]);
        BroadcastPendingCount::dispatch();

        return response()->json(['message' => 'Pembayaran dikonfirmasi.']);
    }

    public function confirmCash(Order $order, InventoryService $inventoryService)
    {
        if ($order->status !== Order::STATUS_PENDING || $order->payment_method !== 'cash') {
            return response()->json(['message' => 'Status pesanan tidak valid.'], 409);
        }

        $order->load('items.menu');
        $items = $order->items->map(fn($i) => ['menu_id' => $i->menu_id, 'quantity' => $i->quantity])->toArray();
        $fulfillment = $inventoryService->canFulfillOrder($items);

        if (! $fulfillment['can_fulfill']) {
            $first = $fulfillment['insufficient_ingredients'][0];
            $name = $first['ingredient_name'] ?? $first['menu_name'] ?? 'item';
            return back()->with('error', "Stok '{$name}' tidak mencukupi. Silakan coba lagi.");
        }

        try {
            DB::transaction(function () use ($order, $inventoryService) {
                $order->update([
                    'status' => Order::STATUS_DIPROSES,
                    'cashier_id' => Auth::id(),
                    'processed_at' => now(),
                ]);

                $inventoryService->processSaleForOrder($order, Auth::id());
            });
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal memproses pesanan: '.$e->getMessage()], 500);
        }

        BroadcastPendingCount::dispatch();

        return response()->json(['message' => 'Pembayaran cash dikonfirmasi.']);
    }

    public function confirmQris(Order $order, InventoryService $inventoryService)
    {
        if ($order->status !== Order::STATUS_PENDING || $order->payment_method !== 'qris') {
            return response()->json(['message' => 'Status pesanan tidak valid.'], 409);
        }

        $order->load('items.menu');
        $items = $order->items->map(fn($i) => ['menu_id' => $i->menu_id, 'quantity' => $i->quantity])->toArray();
        $fulfillment = $inventoryService->canFulfillOrder($items);

        if (! $fulfillment['can_fulfill']) {
            $first = $fulfillment['insufficient_ingredients'][0];
            $name = $first['ingredient_name'] ?? $first['menu_name'] ?? 'item';
            return back()->with('error', "Stok '{$name}' tidak mencukupi. Silakan coba lagi.");
        }

        try {
            DB::transaction(function () use ($order, $inventoryService) {
                // Hapus file bukti setelah dikonfirmasi
                if ($order->payment_proof) {
                    Storage::disk('public')->delete($order->payment_proof);
                }

                $order->update([
                    'status' => Order::STATUS_DIPROSES,
                    'cashier_id' => Auth::id(),
                    'payment_proof' => null,
                    'processed_at' => now(),
                ]);

                $inventoryService->processSaleForOrder($order, Auth::id());
            });
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal memproses pesanan: '.$e->getMessage()], 500);
        }

        BroadcastPendingCount::dispatch();

        return response()->json(['message' => 'Pembayaran QRIS dikonfirmasi.']);
    }

    public function rejectQris(Request $request, Order $order)
    {
        if ($order->status !== Order::STATUS_PENDING || $order->payment_method !== 'qris') {
            return response()->json(['message' => 'Status pesanan tidak valid.'], 409);
        }
        $request->validate(['note' => 'nullable|string|max:255']);

        if ($order->payment_proof) {
            Storage::disk('public')->delete($order->payment_proof);
        }

        $order->update([
            'payment_proof' => null,
            'rejection_note' => $request->note,
        ]);

        return response()->json(['message' => 'Bukti QRIS ditolak.']);
    }

    /**
     * Accept QRIS payment proof and advance order to diproses.
     */

    /**
     * Accept QRIS payment proof and advance order to diproses.
     */
    public function acceptQrisProof(Order $order, InventoryService $inventoryService)
    {
        if ($order->qris_status !== 'proof_submitted') {
            return response()->json(['message' => 'Bukti QRIS tidak dalam status review.'], 409);
        }

        $order->load('items.menu');
        $items = $order->items->map(fn($i) => ['menu_id' => $i->menu_id, 'quantity' => $i->quantity])->toArray();
        $fulfillment = $inventoryService->canFulfillOrder($items);

        if (! $fulfillment['can_fulfill']) {
            $first = $fulfillment['insufficient_ingredients'][0];
            $name = $first['ingredient_name'] ?? $first['menu_name'] ?? 'item';
            return back()->with('error', "Stok '{$name}' tidak mencukupi. Silakan coba lagi.");
        }

        try {
            DB::transaction(function () use ($order, $inventoryService) {
                if ($order->payment_proof) {
                    Storage::disk('public')->delete($order->payment_proof);
                }

                $order->update([
                    'qris_status'   => 'accepted',
                    'status'        => Order::STATUS_DIPROSES,
                    'cashier_id'    => Auth::id(),
                    'payment_proof' => null,
                    'processed_at'  => now(),
                ]);

                $inventoryService->processSaleForOrder($order, Auth::id());
            });
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal memproses pesanan: '.$e->getMessage()], 500);
        }

        broadcast(new OrderQrisReviewed($order, 'accepted', null));

        BroadcastPendingCount::dispatch();

        return response()->json(['message' => 'Bukti QRIS diterima. Pesanan diproses.']);
    }

    /**
     * Reject QRIS payment proof with a required reason.
     */
    public function rejectQrisProof(Request $request, Order $order)
    {
        if ($order->qris_status !== 'proof_submitted') {
            return response()->json(['message' => 'Bukti QRIS tidak dalam status review.'], 409);
        }

        $request->validate(['reason' => 'required|string|max:500']);

        DB::transaction(function () use ($request, $order) {
            if ($order->payment_proof) {
                Storage::disk('public')->delete($order->payment_proof);
            }

            $order->update([
                'qris_status'    => 'rejected',
                'payment_proof'  => null,
                'rejection_note' => $request->reason,
            ]);
        });

        broadcast(new OrderQrisReviewed($order, 'rejected', $request->reason));

        return response()->json(['message' => 'Bukti QRIS ditolak.']);
    }

    /**
     * Request the customer to resubmit their QRIS payment proof.
     */
    public function requestQrisResubmit(Request $request, Order $order)
    {
        if ($order->qris_status !== 'proof_submitted') {
            return response()->json(['message' => 'Bukti QRIS tidak dalam status review.'], 409);
        }

        $request->validate(['reason' => 'required|string|max:500']);

        DB::transaction(function () use ($request, $order) {
            if ($order->payment_proof) {
                Storage::disk('public')->delete($order->payment_proof);
            }

            $order->update([
                'qris_status'    => 'resubmit_requested',
                'payment_proof'  => null,
                'rejection_note' => $request->reason,
            ]);
        });

        broadcast(new OrderQrisReviewed($order, 'resubmit_requested', $request->reason));

        return response()->json(['message' => 'Pengunggahan ulang bukti QRIS diminta.']);
    }

    public function whatsappLink(Request $request, Order $order, WhatsAppReceiptService $waService)
    {
        $request->validate([
            'phone' => 'required|string',
        ]);

        try {
            $waLink = $waService->buildWaMeLink($order, $request->phone);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['wa_link' => $waLink]);
    }
}
