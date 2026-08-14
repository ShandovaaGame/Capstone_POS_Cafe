<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ingredient_batches', function (Blueprint $table) {
            $table->enum('status', ['active', 'inactive'])->default('active')->after('custom_order');
        });

        DB::table('ingredient_batches')->update(['status' => 'active']);
    }

    public function down(): void
    {
        Schema::table('ingredient_batches', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
