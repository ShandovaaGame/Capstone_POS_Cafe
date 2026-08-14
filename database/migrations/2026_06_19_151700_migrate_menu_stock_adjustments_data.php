<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("UPDATE stock_adjustments SET adjustable_type = 'ingredient' WHERE adjustable_type IS NULL");

        Schema::create('_id_mapping_temp', function (Blueprint $table) {
            $table->bigInteger('old_id');
            $table->bigInteger('new_id');
        });

        $rows = DB::table('menu_stock_adjustments')->orderBy('id')->get();
        $mappings = [];

        foreach ($rows as $row) {
            $newId = DB::table('stock_adjustments')->insertGetId([
                'adjustable_type' => 'menu_stock',
                'menu_stock_id' => $row->menu_stock_id,
                'adjustment_type' => $row->adjustment_type,
                'waste_category' => $row->waste_category,
                'quantity' => $row->quantity,
                'quantity_before' => $row->quantity_before,
                'quantity_after' => $row->quantity_after,
                'reason' => $row->reason,
                'reported_by' => $row->reported_by,
                'adjusted_at' => $row->adjusted_at,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
            $mappings[] = ['old_id' => $row->id, 'new_id' => $newId];
        }

        if (! empty($mappings)) {
            DB::table('_id_mapping_temp')->insert($mappings);
        }
    }

    public function down(): void
    {
        DB::table('stock_adjustments')->where('adjustable_type', 'menu_stock')->delete();

        Schema::dropIfExists('_id_mapping_temp');

        DB::statement("UPDATE stock_adjustments SET adjustable_type = NULL WHERE adjustable_type = 'ingredient'");
    }
};
