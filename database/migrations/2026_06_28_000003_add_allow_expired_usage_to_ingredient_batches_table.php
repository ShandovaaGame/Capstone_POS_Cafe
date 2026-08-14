<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ingredient_batches', function (Blueprint $table) {
            $table->boolean('allow_expired_usage')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('ingredient_batches', function (Blueprint $table) {
            $table->dropColumn('allow_expired_usage');
        });
    }
};
