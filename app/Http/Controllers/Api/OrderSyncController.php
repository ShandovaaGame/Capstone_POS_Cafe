<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IngredientBatch;
use App\Models\Menu;
use App\Models\MenuIngredient;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class OrderSyncController extends Controller
{
    /**
     * Sync offline orders from PWA cashier.
     *
     * Idempotent: skips orders whose UUID already exists on the server.
     * Soft stock: deducts stock without validation — stock shortage does NOT fail the order.
     * Kitchen bypass: order goes directly to 'selesai'.
     * No Receivable: payment is direct, so the Receivable boot trigger never fires.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'orders'                                    => 'required|array|min:1',
            'orders.*.uuid'                             => 'required|string|max:100',
            'orders.*.items'                            => 'required|array|min:1',
            'orders.*.items.*.menu_id'                  => 'required|integer|exists:menus,id',
            'orders.*.items.*.quantity'                 => 'required|integer|min:1',
            'orders.*.items.*.price'                    => 'required|integer|min:0',
            'orders.*.paymentMethod'                    => 'required|string|max:50',
            'orders.*.customerName'                     => 'nullable|string|max:255',
            'orders.*.isMahasiswa'                      => 'nullable|boolean',
            'orders.*.total'                            => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $inventoryService = app(InventoryService::class);
        $synced = [];
        $failed = [];
        $total = count($request->orders);

        foreach ($request->orders as $orderData) {
            $uuid = $orderData['uuid'];

            $existingOrder = Order::where('uuid', $uuid)->first();
            if ($existingOrder) {
                $synced[] = [
                    'localUuid'      => $uuid,
                    'serverOrderCode' => $existingOrder->order_code,
                    'serverId'        => $existingOrder->id,
                ];
                continue;
            }

            try {
                $order = $this->createOrder($orderData);

                // ── Pre-sync snapshot: collect affected ingredient IDs & current stock ──
                $ingredientIds = $this->collectIngredientIds($orderData);
                $beforeStock = $this->getIngredientStockTotals($ingredientIds);

                // Soft stock: failure does NOT reject the order
                try {
                    $inventoryService->processSaleForOrder($order, Auth::id(), skipStockValidation: true);
                } catch (\Exception $e) {
                    Log::warning("Soft stock deduction failed for synced order {$order->order_code}", [
                        'uuid'  => $uuid,
                        'error' => $e->getMessage(),
                    ]);
                }

                // ── Post-sync reconciliation: compare stock before vs after ──
                $reconciliation = $this->buildReconciliation($ingredientIds, $beforeStock);

                $synced[] = [
                    'localUuid'      => $uuid,
                    'serverOrderCode' => $order->order_code,
                    'serverId'        => $order->id,
                    'reconciliation'  => $reconciliation,
                ];
            } catch (\Exception $e) {
                Log::error("Order sync failed for uuid {$uuid}", [
                    'error' => $e->getMessage(),
                ]);
                $failed[] = [
                    'localUuid' => $uuid,
                    'reason'    => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'synced'  => $synced,
            'failed'  => $failed,
            'summary' => [
                'total'  => $total,
                'synced' => count($synced),
                'failed' => count($failed),
            ],
        ]);
    }

    /** @throws \Exception when a menu referenced by an item is not found */
    private function createOrder(array $orderData): Order
    {
        return DB::transaction(function () use ($orderData) {
            $order = Order::create([
                'uuid'           => $orderData['uuid'],
                'cashier_id'     => Auth::id(),
                'order_type'     => 'cashier',
                'payment_method' => $orderData['paymentMethod'],
                'customer_name'  => $orderData['customerName'] ?? null,
                'status'         => Order::STATUS_SELESAI,
                'total_amount'   => 0,
            ]);

            $menuIds = collect($orderData['items'])->pluck('menu_id')->unique()->all();
            $menus = Menu::whereIn('id', $menuIds)->get()->keyBy('id');

            $total = 0;
            $itemsToInsert = [];

            foreach ($orderData['items'] as $item) {
                $menu = $menus->get($item['menu_id']);
                if (! $menu) {
                    throw new \Exception("Menu not found: {$item['menu_id']}");
                }

                $quantity = (int) $item['quantity'];
                $price    = (int) $item['price'];
                $subtotal = $price * $quantity;

                $itemsToInsert[] = [
                    'order_id'   => $order->id,
                    'menu_id'    => $menu->id,
                    'quantity'   => $quantity,
                    'unit_price' => $price,
                    'subtotal'   => $subtotal,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $total += $subtotal;
            }

            OrderItem::insert($itemsToInsert);

            $order->update(['total_amount' => $total]);

            return $order;
        });
    }

    /**
     * Collect all unique ingredient IDs affected by this order's items.
     *
     * Only menus with recipe-based ingredients (menu_ingredients) affect IngredientBatch stock.
     * Menu-stock-only menus (no recipe) are excluded since they don't touch IngredientBatch.
     *
     * @param  array  $orderData
     * @return int[]
     */
    private function collectIngredientIds(array $orderData): array
    {
        $menuIds = collect($orderData['items'])->pluck('menu_id')->unique()->values()->all();

        return MenuIngredient::whereIn('menu_id', $menuIds)
            ->distinct()
            ->pluck('ingredient_id')
            ->values()
            ->all();
    }

    /**
     * Get current total stock for the given ingredient IDs, keyed by ingredient_id.
     *
     * @param  int[]  $ingredientIds
     * @return array<int, float>
     */
    private function getIngredientStockTotals(array $ingredientIds): array
    {
        if (empty($ingredientIds)) {
            return [];
        }

        return IngredientBatch::whereIn('ingredient_id', $ingredientIds)
            ->select('ingredient_id', DB::raw('SUM(quantity) as total'))
            ->groupBy('ingredient_id')
            ->pluck('total', 'ingredient_id')
            ->map(fn ($v) => (float) $v)
            ->all();
    }

    /**
     * Build reconciliation data comparing stock before and after the sync.
     *
     * Logs each per-ingredient delta for audit trail.
     *
     * @param  int[]            $ingredientIds
     * @param  array<int, float> $beforeStock
     * @return array<int, array{ingredient_id: int, before: float, after: float, delta: float}>
     */
    private function buildReconciliation(array $ingredientIds, array $beforeStock): array
    {
        if (empty($ingredientIds)) {
            return [];
        }

        $afterStock = $this->getIngredientStockTotals($ingredientIds);

        $reconciliation = [];

        foreach ($beforeStock as $id => $before) {
            $after = $afterStock[$id] ?? 0;
            $delta = $after - $before;

            $reconciliation[] = [
                'ingredient_id' => (int) $id,
                'before' => $before,
                'after'  => $after,
                'delta'  => round($delta, 2),
            ];

            Log::info("Delta reconciliation: ingredient {$id} went from {$before} to {$after} (delta: {$delta})");
        }

        return $reconciliation;
    }
}
