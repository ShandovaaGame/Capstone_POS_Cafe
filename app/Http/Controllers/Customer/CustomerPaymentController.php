<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CustomerPaymentController extends Controller
{
    public function showChoose(Order $order): Response
    {
        if ($order->status !== Order::STATUS_PENDING) {
            return Inertia::location('/pelanggan/menu');
        }

        $payload = Cache::remember("order_choose_{$order->id}", 120, function () use ($order) {
            $order->load(['items.menu', 'cafeTable']);
            return [
                'order'        => $order->only(['id', 'order_code', 'total_amount', 'customer_name']),
                'items'        => $order->items->map(fn($i) => [
                    'name'     => $i->menu->name,
                    'qty'      => $i->quantity,
                    'subtotal' => $i->subtotal,
                ])->values(),
                'table_number' => $order->cafeTable?->table_number,
            ];
        });

        return Inertia::render('Pelanggan/Payment/Choose', $payload);
    }

    public function chooseCash(Request $request, Order $order): JsonResponse
    {
        if ($order->status !== Order::STATUS_PENDING) {
            return response()->json(['message' => 'Status pesanan tidak valid.'], 409);
        }
        DB::transaction(function () use ($order) {
            $updates = ['payment_method' => 'cash'];
            if (!$order->order_code) {
                $updates['order_code'] = Order::generateCode();
            }
            $order->update($updates);
        });
        return response()->json(['message' => 'ok', 'order_code' => $order->fresh()->order_code]);
    }

    public function chooseQris(Request $request, Order $order): JsonResponse
    {
        if ($order->status !== Order::STATUS_PENDING) {
            return response()->json(['message' => 'Status pesanan tidak valid.'], 409);
        }
        $order->update(['payment_method' => 'qris']);

        [$qrisImage, $qrisName] = Cache::remember('qris_settings', 600, fn() => [
            asset('storage/' . Setting::get('qris_image', 'qris/qris-w9cafe.png')),
            Setting::get('qris_name', 'W9 Cafe'),
        ]);

        return response()->json([
            'qris_image'   => $qrisImage,
            'qris_name'    => $qrisName,
            'total_amount' => $order->total_amount,
        ]);
    }

    public function showCashStatus(Order $order): RedirectResponse
    {
        return redirect("/pelanggan/pesanan/{$order->order_code}/status");
    }

    public function showQrisUpload(Order $order): Response
    {
        if (in_array($order->status, [Order::STATUS_DIPROSES, Order::STATUS_SELESAI])) {
            return Inertia::render('Pelanggan/Payment/QrisStatus', ['order' => $this->orderData($order)]);
        }

        $rejectedMessage = ($order->payment_method === 'qris' && $order->rejection_note && !$order->payment_proof)
            ? $order->rejection_note
            : null;

        return Inertia::render('Pelanggan/Payment/QrisUpload', [
            'order'           => $order->only(['id', 'order_code', 'total_amount']),
            'qrisImage'       => asset('storage/' . Setting::get('qris_image', 'qris/qris-w9cafe.png')),
            'qrisName'        => Setting::get('qris_name', 'W9 Cafe'),
            'totalAmount'     => $order->total_amount,
            'rejectedMessage' => $rejectedMessage,
            'resubmitCount'   => $order->resubmit_count ?? 0,
        ]);
    }

    public function uploadQrisProof(Request $request, Order $order): JsonResponse
    {
        $request->validate([
            'proof' => 'required|file|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        if ($order->status !== Order::STATUS_PENDING) {
            return response()->json(['message' => 'Status pesanan tidak valid.'], 409);
        }

        if ($order->payment_proof) {
            Storage::disk('public')->delete($order->payment_proof);
        }

        $path = $request->file('proof')->store('proofs', 'public');

        DB::transaction(function () use ($order, $path) {
            $updates = [
                'payment_proof'  => $path,
                'payment_method' => 'qris',
                'rejection_note' => null,
            ];
            // Assign order_code on first upload; keep existing on re-upload after rejection
            if (!$order->order_code) {
                $updates['order_code'] = Order::generateCode();
            }
            $order->update($updates);
        });

        return response()->json([
            'message'    => 'Bukti berhasil dikirim',
            'order_code' => $order->fresh()->order_code,
        ]);
    }

    public function showQrisStatus(Order $order): Response
    {
        return Inertia::render('Pelanggan/Payment/QrisStatus', ['order' => $this->orderData($order)]);
    }

    private function orderData(Order $order): array
    {
        return [
            'id'             => $order->id,
            'order_code'     => $order->order_code,
            'status'         => $order->status,
            'total_amount'   => $order->total_amount,
            'payment_method' => $order->payment_method,
            'rejection_note' => $order->rejection_note,
        ];
    }
}
