<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Drop temporary mapping table
        Schema::dropIfExists('_id_mapping_temp');

        // Step 2: Drop old table
        Schema::dropIfExists('menu_stock_adjustments');

        // Step 3: Make adjustable_type NOT NULL now that all data is migrated
        Schema::table('stock_adjustments', function (Blueprint $table) {
            $table->string('adjustable_type', 50)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        // Recreate menu_stock_adjustments table (structure only — can't restore data)
        Schema::create('menu_stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_stock_id')->constrained()->cascadeOnDelete();
            $table->string('adjustment_type');
            $table->string('waste_category')->nullable();
            $table->decimal('quantity', 15, 2);
            $table->decimal('quantity_before', 15, 2);
            $table->decimal('quantity_after', 15, 2);
            $table->text('reason')->nullable();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('adjusted_at');
            $table->timestamps();
        });

        // Make adjustable_type nullable again
        Schema::table('stock_adjustments', function (Blueprint $table) {
            $table->string('adjustable_type', 50)->nullable()->change();
        });
    }
};
