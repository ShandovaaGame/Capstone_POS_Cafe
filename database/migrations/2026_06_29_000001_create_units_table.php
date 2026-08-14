<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('abbreviation', 10);
            $table->enum('unit_type', ['weight', 'volume', 'count']);
            $table->foreignId('base_unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->decimal('conversion_factor', 16, 6)->default(1);
            $table->timestamps();
        });

        $units = [
            ['name' => 'gram', 'abbreviation' => 'g', 'unit_type' => 'weight', 'conversion_factor' => 1],
            ['name' => 'kg', 'abbreviation' => 'kg', 'unit_type' => 'weight', 'conversion_factor' => 1000],
            ['name' => 'ml', 'abbreviation' => 'ml', 'unit_type' => 'volume', 'conversion_factor' => 1],
            ['name' => 'liter', 'abbreviation' => 'L', 'unit_type' => 'volume', 'conversion_factor' => 1000],
            ['name' => 'pcs', 'abbreviation' => 'pcs', 'unit_type' => 'count', 'conversion_factor' => 1],
            ['name' => 'sachet', 'abbreviation' => 'sct', 'unit_type' => 'count', 'conversion_factor' => 1],
            ['name' => 'sdm', 'abbreviation' => 'sdm', 'unit_type' => 'volume', 'conversion_factor' => 15],
            ['name' => 'sdt', 'abbreviation' => 'sdt', 'unit_type' => 'volume', 'conversion_factor' => 5],
        ];

        foreach ($units as $u) {
            DB::table('units')->insert([
                'name' => $u['name'],
                'abbreviation' => $u['abbreviation'],
                'unit_type' => $u['unit_type'],
                'conversion_factor' => $u['conversion_factor'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $getId = fn($n) => DB::table('units')->where('name', $n)->value('id');
        DB::table('units')->where('name', 'kg')->update(['base_unit_id' => $getId('gram')]);
        DB::table('units')->where('name', 'liter')->update(['base_unit_id' => $getId('ml')]);
        DB::table('units')->where('name', 'sdm')->update(['base_unit_id' => $getId('ml')]);
        DB::table('units')->where('name', 'sdt')->update(['base_unit_id' => $getId('ml')]);
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
