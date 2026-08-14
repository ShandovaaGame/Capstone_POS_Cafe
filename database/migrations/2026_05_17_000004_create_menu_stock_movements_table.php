<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_stock_id')->constrained('menu_stocks')->cascadeOnDelete();
            $table->foreignId('menu_stock_batch_id')->nullable()->constrained('menu_stock_batches')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained('order_items')->nullOnDelete();
            $table->foreignId('menu_stock_adjustment_id')->nullable()->constrained('menu_stock_adjustments')->nullOnDelete();
            $table->enum('movement_type', [
                'purchase',
                'sale',
                'waste',
                'adjustment_increase',
                'adjustment_decrease',
                'correction',
            ]);
            $table->string('source_type')->nullable();
            $table->string('source_id')->nullable();
            $table->decimal('quantity_before', 12, 2);
            $table->decimal('quantity_change', 12, 2);
            $table->decimal('quantity_after', 12, 2);
            $table->decimal('unit_cost', 12, 2)->nullable();
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['menu_stock_id', 'created_at']);
            $table->index(['movement_type', 'created_at']);
            $table->index(['source_type', 'source_id']);
            $table->index('menu_stock_id');
            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_stock_movements');
    }
};
