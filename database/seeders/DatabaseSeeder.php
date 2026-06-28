<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CategorySeeder::class,
            MenuSeeder::class,
            CafeTableSeeder::class,
            SettingSeeder::class,
            TransactionHistorySeeder::class,
            AssociationHistorySeeder::class,
            PredictionHistorySeeder::class,
            IngredientUsageSeeder::class,
            RecipeIngredientSeeder::class,
        ]);
    }
}
