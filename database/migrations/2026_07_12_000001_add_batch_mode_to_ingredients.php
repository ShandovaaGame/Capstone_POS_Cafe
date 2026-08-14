<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('ingredients', 'batch_mode')) {
            Schema::table('ingredients', function (Blueprint $table) {
                $table->string('batch_mode', 20)->default('fefo')->after('low_stock_threshold');
            });
        }
    }

    public function down(): void
    {
        Schema::table('ingredients', function (Blueprint $table) {
            $table->dropColumn('batch_mode');
        });
    }
};
