<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_adjustments', function (Blueprint $table) {
            $table->enum('status', ['active', 'cancelled'])->default('active')->after('reason');
            $table->text('cancel_reason')->nullable()->after('status');
        });

        DB::table('stock_adjustments')->update(['status' => 'active']);
    }

    public function down(): void
    {
        Schema::table('stock_adjustments', function (Blueprint $table) {
            $table->dropColumn('cancel_reason');
            $table->dropColumn('status');
        });
    }
};
