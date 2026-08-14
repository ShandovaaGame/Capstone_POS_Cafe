<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('staff_sessions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('user_id')->constrained()->nullOnDelete();
            $table->string('type', 20); // 'cashier' or 'kitchen'
            $table->string('session_id', 255)->nullable();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamp('last_activity_at')->useCurrent();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
            $table->index(['is_active', 'last_activity_at']);
            $table->index('session_id');
            $table->index('type');
        });

        // Skip data migration — local cashier_sessions has different schema (shift tracking)
        Schema::dropIfExists('cashier_sessions');
        Schema::dropIfExists('kitchen_sessions');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('cashier_sessions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('user_id')->constrained()->nullOnDelete();
            $table->string('session_id', 255)->nullable();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamp('last_activity_at')->useCurrent();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
            $table->index(['is_active', 'last_activity_at']);
            $table->index('session_id');
        });

        Schema::create('kitchen_sessions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('user_id')->constrained()->nullOnDelete();
            $table->string('session_id', 255)->nullable();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamp('last_activity_at')->useCurrent();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
            $table->index(['is_active', 'last_activity_at']);
            $table->index('session_id');
        });

        DB::statement("
            INSERT INTO cashier_sessions (id, user_id, session_id, started_at, ended_at, last_activity_at, is_active, created_at, updated_at)
            SELECT id, user_id, session_id, started_at, ended_at, last_activity_at, is_active, created_at, updated_at
            FROM staff_sessions WHERE type = 'cashier'
        ");

        DB::statement("
            INSERT INTO kitchen_sessions (id, user_id, session_id, started_at, ended_at, last_activity_at, is_active, created_at, updated_at)
            SELECT id, user_id, session_id, started_at, ended_at, last_activity_at, is_active, created_at, updated_at
            FROM staff_sessions WHERE type = 'kitchen'
        ");

        Schema::dropIfExists('staff_sessions');
    }
};
