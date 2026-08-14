<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingredient_unit_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ingredient_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('from_unit_id')->constrained('units');
            $table->foreignId('to_unit_id')->constrained('units');
            $table->decimal('conversion_factor', 16, 6);
            $table->timestamps();
            $table->unique(['from_unit_id', 'to_unit_id', 'ingredient_id']);
        });

        $get = fn ($n) => DB::table('units')->where('name', $n)->value('id');
        $convs = [
            ['from' => 'kg', 'to' => 'gram', 'factor' => 1000],
            ['from' => 'gram', 'to' => 'kg', 'factor' => 0.001],
            ['from' => 'liter', 'to' => 'ml', 'factor' => 1000],
            ['from' => 'ml', 'to' => 'liter', 'factor' => 0.001],
            ['from' => 'sdm', 'to' => 'ml', 'factor' => 15],
            ['from' => 'ml', 'to' => 'sdm', 'factor' => 1 / 15],
            ['from' => 'sdt', 'to' => 'ml', 'factor' => 5],
            ['from' => 'ml', 'to' => 'sdt', 'factor' => 0.2],
        ];

        foreach ($convs as $c) {
            DB::table('ingredient_unit_conversions')->insert([
                'from_unit_id' => $get($c['from']),
                'to_unit_id' => $get($c['to']),
                'conversion_factor' => $c['factor'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ingredient_unit_conversions');
    }
};
