<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('stock_adjustments', 'waste_category')) {
            Schema::table('stock_adjustments', function (Blueprint $table) {
                $table->renameColumn('waste_category', 'category');
            });
        }
    }

    public function down(): void
    {
        Schema::table('stock_adjustments', function (Blueprint $table) {
            $table->renameColumn('category', 'waste_category');
        });
    }
};
