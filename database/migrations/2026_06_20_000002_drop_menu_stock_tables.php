<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Drop FKs from stock_adjustments first (menu_stock_id FK blocks dropping menu_stocks)
        Schema::table('stock_adjustments', function (Blueprint $table) {
            $table->dropForeign(['menu_stock_id']);
        });
        Schema::table('stock_adjustments', function (Blueprint $table) {
            $table->dropForeign(['menu_id']);
        });
        Schema::table('stock_adjustments', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
        });

        // 2. Drop columns from stock_adjustments
        Schema::table('stock_adjustments', function (Blueprint $table) {
            $table->dropColumn('menu_stock_id');
            $table->dropColumn('menu_id');
            $table->dropColumn('order_id');
        });

        // 3. Drop menu_stock tables (dependent tables first)
        Schema::dropIfExists('menu_stock_movements');
        Schema::dropIfExists('menu_stock_batches');
        Schema::dropIfExists('menu_stocks');

        // 4. Drop mapping temp table from T1 migration
        Schema::dropIfExists('_mapping_menu_stock');
    }

    public function down(): void
    {
        // Recreate menu_stocks table
        Schema::create('menu_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained()->cascadeOnDelete();
            $table->string('unit');
            $table->decimal('low_stock_threshold', 10, 2)->default(0);
            $table->string('batch_mode')->default('fefo');
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('menu_stock_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_stock_id')->constrained('menu_stocks')->cascadeOnDelete();
            $table->decimal('quantity', 15, 2)->default(0);
            $table->date('expiry_date')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->decimal('cost_per_unit', 15, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('menu_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adjustment_id')->nullable()->constrained('stock_adjustments')->nullOnDelete();
            $table->foreignId('menu_stock_id')->constrained('menu_stocks')->cascadeOnDelete();
            $table->foreignId('menu_stock_batch_id')->nullable()->constrained('menu_stock_batches')->nullOnDelete();
            $table->string('movement_type');
            $table->timestamps();
        });

        // Restore columns on stock_adjustments
        Schema::table('stock_adjustments', function (Blueprint $table) {
            $table->foreignId('menu_stock_id')->nullable()->constrained('menu_stocks')->nullOnDelete();
        });
        Schema::table('stock_adjustments', function (Blueprint $table) {
            $table->foreignId('menu_id')->nullable()->after('menu_stock_id')->constrained('menus')->nullOnDelete();
        });
        Schema::table('stock_adjustments', function (Blueprint $table) {
            $table->foreignId('order_id')->nullable()->after('menu_id')->constrained('orders')->nullOnDelete();
        });
    }
};
