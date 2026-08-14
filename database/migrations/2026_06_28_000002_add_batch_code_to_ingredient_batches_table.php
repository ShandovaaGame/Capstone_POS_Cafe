<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ingredient_batches', function (Blueprint $table) {
            $table->string('batch_code', 50)->nullable()->unique()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('ingredient_batches', function (Blueprint $table) {
            $table->dropColumn('batch_code');
        });
    }
};
