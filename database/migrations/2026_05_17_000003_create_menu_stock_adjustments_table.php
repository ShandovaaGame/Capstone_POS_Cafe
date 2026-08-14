<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_stock_id')->constrained('menu_stocks')->cascadeOnDelete();
            $table->enum('adjustment_type', ['increase', 'decrease']);
            $table->string('waste_category')->nullable();
            $table->decimal('quantity', 12, 2);
            $table->decimal('quantity_before', 12, 2);
            $table->decimal('quantity_after', 12, 2);
            $table->string('reason', 255)->nullable();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('adjusted_at')->useCurrent();
            $table->timestamps();

            $table->index(['menu_stock_id', 'adjusted_at']);
            $table->index(['adjustment_type', 'adjusted_at']);
            $table->index(['waste_category', 'adjusted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_stock_adjustments');
    }
};
