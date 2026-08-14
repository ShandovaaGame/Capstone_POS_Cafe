<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Idempotency key for cashier POS submissions
            $table->uuid('uuid')->nullable()->unique()->after('id');

            // QRIS payment proof flow
            $table->string('qris_status')->nullable()->after('payment_proof');
            $table->unsignedTinyInteger('resubmit_count')->default(0)->after('qris_status');

            // WhatsApp receipt sharing
            $table->string('whatsapp_phone')->nullable()->after('customer_phone');

            // Who processed the order (separate from cashier_id who took the order)
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete()->after('cashier_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['processed_by']);
            $table->dropColumn(['uuid', 'qris_status', 'resubmit_count', 'whatsapp_phone', 'processed_by']);
        });
    }
};
