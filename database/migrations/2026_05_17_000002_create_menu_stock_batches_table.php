<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_stock_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_stock_id')->constrained('menu_stocks')->cascadeOnDelete();
            $table->decimal('quantity', 12, 2);
            $table->date('expiry_date')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->decimal('cost_per_unit', 12, 2)->default(0);

            $table->index(['menu_stock_id', 'expiry_date']);
            $table->index('received_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_stock_batches');
    }
};
