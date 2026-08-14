<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $receivables = DB::table('receivables')->where('paid_amount', '>', 0)->get(['id', 'paid_amount', 'created_at', 'updated_at']);

        foreach ($receivables as $r) {
            DB::table('receivable_payments')->insert([
                'receivable_id' => $r->id,
                'amount' => $r->paid_amount,
                'payment_date' => $r->updated_at ?? $r->created_at ?? now(),
                'notes' => 'Migrasi saldo awal',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('receivable_payments')->where('notes', 'Migrasi saldo awal')->delete();
    }
};
