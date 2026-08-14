<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_adjustments', function (Blueprint $table) {
            $table->foreignId('menu_id')->nullable()->after('menu_stock_id')
                ->constrained('menus')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->after('menu_id')
                ->constrained('orders')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stock_adjustments', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->dropForeign(['menu_id']);
            $table->dropColumn('order_id');
            $table->dropColumn('menu_id');
        });
    }
};
