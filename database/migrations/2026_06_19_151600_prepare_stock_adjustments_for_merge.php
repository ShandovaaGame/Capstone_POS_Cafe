<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_adjustments', function (Blueprint $table) {
            $table->string('adjustable_type', 50)->nullable()->after('id');

            $table->foreignId('ingredient_id')->nullable()->change();

            $table->foreignId('menu_stock_id')->nullable()->after('ingredient_id')
                ->constrained('menu_stocks')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stock_adjustments', function (Blueprint $table) {
            $table->dropForeign(['menu_stock_id']);
            $table->dropColumn('menu_stock_id');
            $table->dropColumn('adjustable_type');
            $table->foreignId('ingredient_id')->nullable(false)->change();
        });
    }
};
