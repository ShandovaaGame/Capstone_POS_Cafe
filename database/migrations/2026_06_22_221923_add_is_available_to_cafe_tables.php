<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('cafe_tables', 'is_available')) {
            Schema::table('cafe_tables', function (Blueprint $table) {
                $table->boolean('is_available')->default(true)->after('qr_code');
            });
        }
    }

    public function down(): void
    {
        Schema::table('cafe_tables', function (Blueprint $table) {
            $table->dropColumn('is_available');
        });
    }
};
