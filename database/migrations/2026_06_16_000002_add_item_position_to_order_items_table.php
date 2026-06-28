<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedSmallInteger('item_position')->default(1)->after('order_id');
        });

        // Backfill based on insertion order (id) within each order.
        // ROW_NUMBER() OVER (PARTITION BY order_id ORDER BY id) gives each item
        // its 1-based position in the order it was inserted.
        DB::statement('
            UPDATE order_items oi
            SET item_position = sub.rn
            FROM (
                SELECT id,
                       ROW_NUMBER() OVER (PARTITION BY order_id ORDER BY id) AS rn
                FROM order_items
            ) sub
            WHERE oi.id = sub.id
        ');
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('item_position');
        });
    }
};
