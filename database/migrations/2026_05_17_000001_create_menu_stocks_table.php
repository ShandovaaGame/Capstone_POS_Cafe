<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('menus')->cascadeOnDelete();
            $table->enum('unit', ['gram', 'kg', 'ml', 'liter', 'pcs', 'sachet', 'sdm', 'sdt']);
            $table->decimal('low_stock_threshold', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('batch_mode')->default('fefo');
            $table->timestamps();
            $table->softDeletes();

            $table->unique('menu_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_stocks');
    }
};
