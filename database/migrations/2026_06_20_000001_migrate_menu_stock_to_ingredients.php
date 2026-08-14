<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('_mapping_menu_stock', function (Blueprint $table) {
            $table->bigInteger('old_menu_stock_id');
            $table->bigInteger('new_ingredient_id');
            $table->bigInteger('menu_id');
        });

        $menuStocks = DB::table('menu_stocks')
            ->join('menus', 'menus.id', '=', 'menu_stocks.menu_id')
            ->select('menu_stocks.*', 'menus.name as menu_name')
            ->get();

        foreach ($menuStocks as $ms) {
            $ingredientId = DB::table('ingredients')->insertGetId([
                'name' => $ms->menu_name . ' (Stok)',
                'unit' => $ms->unit,
                'low_stock_threshold' => $ms->low_stock_threshold ?? 0,
                'is_active' => $ms->is_active,
                'batch_mode' => $ms->batch_mode ?? 'fefo',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('_mapping_menu_stock')->insert([
                'old_menu_stock_id' => $ms->id,
                'new_ingredient_id' => $ingredientId,
                'menu_id' => $ms->menu_id,
            ]);

            DB::table('menu_ingredients')->insert([
                'menu_id' => $ms->menu_id,
                'ingredient_id' => $ingredientId,
                'quantity_used' => 1,
            ]);

            $batches = DB::table('menu_stock_batches')
                ->where('menu_stock_id', $ms->id)
                ->get();

            foreach ($batches as $batch) {
                DB::table('ingredient_batches')->insert([
                    'ingredient_id' => $ingredientId,
                    'quantity' => $batch->quantity,
                    'expiry_date' => $batch->expiry_date,
                    'received_at' => $batch->received_at,
                    'cost_per_unit' => $batch->cost_per_unit,
                ]);
            }
        }

        $mappings = DB::table('_mapping_menu_stock')->get();
        foreach ($mappings as $map) {
            DB::table('stock_adjustments')
                ->where('menu_stock_id', $map->old_menu_stock_id)
                ->update([
                    'ingredient_id' => $map->new_ingredient_id,
                    'menu_stock_id' => null,
                    'adjustable_type' => 'ingredient',
                ]);
        }

        $movements = DB::table('menu_stock_movements')->get();
        foreach ($movements as $mov) {
            $map = DB::table('_mapping_menu_stock')
                ->where('old_menu_stock_id', $mov->menu_stock_id)
                ->first();

            if ($map) {
                DB::table('stock_movements')->insert([
                    'ingredient_id' => $map->new_ingredient_id,
                    'ingredient_batch_id' => null,
                    'order_id' => $mov->order_id,
                    'order_item_id' => $mov->order_item_id,
                    'stock_adjustment_id' => $mov->adjustment_id,
                    'movement_type' => $mov->movement_type,
                    'source_type' => $mov->source_type,
                    'source_id' => $mov->source_id,
                    'quantity_before' => $mov->quantity_before,
                    'quantity_change' => $mov->quantity_change,
                    'quantity_after' => $mov->quantity_after,
                    'unit_cost' => $mov->unit_cost,
                    'reference' => $mov->reference,
                    'notes' => $mov->notes,
                    'recorded_by' => $mov->recorded_by,
                    'created_at' => $mov->created_at ?? now(),
                    'updated_at' => $mov->updated_at ?? now(),
                ]);
            }
        }


        DB::table('stock_adjustments')
            ->where('adjustable_type', 'menu_stock')
            ->update(['adjustable_type' => 'ingredient']);
    }

    public function down(): void
    {

        $mappings = DB::table('_mapping_menu_stock')->get();
        foreach ($mappings as $map) {
            DB::table('menu_ingredients')
                ->where('ingredient_id', $map->new_ingredient_id)
                ->delete();
            DB::table('ingredients')->where('id', $map->new_ingredient_id)->delete();
        }

        Schema::dropIfExists('_mapping_menu_stock');
    }
};
