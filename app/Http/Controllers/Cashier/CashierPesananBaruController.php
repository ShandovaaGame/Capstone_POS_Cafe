<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Category;
use App\Models\Menu;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\InventoryService;
use App\Services\OrderPromotionService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Ramsey\Uuid\Uuid;

class CashierPesananBaruController extends Controller
{
    public function index()
    {
        // Cache 5 menit — menu jarang berubah, admin bisa clear cache jika update menu
        $categories = Cache::remember('menu_categories_cashier', 300, fn () => Category::with([
            'menus' => fn ($q) => $q->orderBy('name'),
        ])
            ->orderBy('name')
            ->get()
        );

        return Inertia::render('Kasir/PesananBaru', compact('categories'));
    }

    public function store(
        StoreOrderRequest $request,
        OrderPromotionService $orderPromotionService,
        InventoryService $inventoryService,
    ) {
        $uuid = $request->uuid ?? (string) Uuid::uuid7();
        $orderModel = null;

        $attempt = function () use ($request, $orderPromotionService, $inventoryService, &$uuid, &$orderModel) {
            DB::transaction(function () use ($request, $orderPromotionService, $inventoryService, &$uuid, &$orderModel) {
                $selectedPromotionIds = $request->input('promotion_ids', []);

                $order = Order::create([
                    'uuid' => $uuid,
                    'cashier_id' => Auth::id(),
                    'order_type' => 'cashier',
                    'payment_method' => $request->payment_method,
                    'customer_name' => $request->customer_name,
                    'status' => Order::STATUS_PENDING,
                    'total_amount' => 0,
                ]);

                $isMahasiswa = (bool) $request->input('is_mahasiswa', false);
                $total = 0;
                $appliedPromotions = [];
                $itemsToInsert = [];

                // Bulk-fetch semua menu sekaligus — hindari N+1 query
                $menuIds = collect($request->items)->pluck('menu_id')->unique()->all();
                $menus = Menu::whereIn('id', $menuIds)->get()->keyBy('id');

                foreach ($request->items as $item) {
                    $menu = $menus->get($item['menu_id']);

                    $lineCalculation = $orderPromotionService->calculateLine(
                        $menu,
                        (int) $item['quantity'],
                        $isMahasiswa,
                        $selectedPromotionIds,
                    );

                    $itemsToInsert[] = [
                        'order_id' => $order->id,
                        'menu_id' => $menu->id,
                        'quantity' => $item['quantity'],
                        'unit_price' => $lineCalculation['unit_price'],
                        'subtotal' => $lineCalculation['subtotal'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    if ($lineCalculation['applied_promotion'] !== null) {
                        $appliedPromotions[] = $lineCalculation['applied_promotion'];
                    }

                    $total += $lineCalculation['subtotal'];
                }

                OrderItem::insert($itemsToInsert);

                $order->update(['total_amount' => $total]);
                $orderModel = $order;

                $orderPromotionService->persistOrderPromotions($order, $appliedPromotions);

                $inventoryService->processSaleForOrder($order, Auth::id());
            });
        };

        try {
            $attempt();
            return back()
                ->with('success', 'Pesanan berhasil dibuat')
                ->with('order_id', $orderModel?->id)
                ->with('order_total', $orderModel?->total_amount)
                ->with('order_code', $orderModel?->order_code);
        } catch (UniqueConstraintViolationException) {
            $uuid = (string) Uuid::uuid7();
            $attempt();
            return back()
                ->with('success', 'Pesanan berhasil dibuat')
                ->with('order_id', $orderModel?->id)
                ->with('order_total', $orderModel?->total_amount)
                ->with('order_code', $orderModel?->order_code);
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Gagal memproses pesanan: '.$e->getMessage());
        }
    }
}
