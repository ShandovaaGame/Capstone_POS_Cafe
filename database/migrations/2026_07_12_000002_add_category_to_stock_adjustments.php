<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('stock_adjustments', 'category')) {
            Schema::table('stock_adjustments', function (Blueprint $table) {
                $table->string('category', 50)->nullable()->after('adjustment_type');
            });
        }
    }

    public function down(): void
    {
        Schema::table('stock_adjustments', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
