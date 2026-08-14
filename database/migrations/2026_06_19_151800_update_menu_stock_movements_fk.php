<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_stock_movements', function (Blueprint $table) {
            $table->foreignId('adjustment_id')->nullable()->after('id')
                ->constrained('stock_adjustments')->nullOnDelete();
        });

        if (Schema::hasTable('_id_mapping_temp')) {
            $mappingCount = DB::table('_id_mapping_temp')->count();
            if ($mappingCount > 0) {
                DB::statement("
                    UPDATE menu_stock_movements msm
                    SET adjustment_id = map.new_id::bigint
                    FROM _id_mapping_temp map
                    WHERE map.old_id = msm.menu_stock_adjustment_id
                ");
            }
        }

        try {
            Schema::table('menu_stock_movements', function (Blueprint $table) {
                $table->dropForeign(['menu_stock_adjustment_id']);
            });
        } catch (\Exception $e) {
            // Constraint might not exist or already dropped — ignore
        }
    }

    public function down(): void
    {
        Schema::table('menu_stock_movements', function (Blueprint $table) {
            $table->dropForeign(['adjustment_id']);
            $table->dropColumn('adjustment_id');
        });
    }
};
