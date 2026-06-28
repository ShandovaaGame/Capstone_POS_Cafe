<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * RecipeIngredientSeeder
 *
 * Melakukan 4 hal berurutan:
 *   1. Upsert 24 bahan baku (ingredients) dengan threshold & unit lengkap
 *   2. Tambah ingredient_batches (stok awal + cost/unit per bahan)
 *   3. Buat resep (menu_ingredients) untuk semua 22 menu W9 Cafe
 *   4. Hitung ulang daily_ingredient_usages dari data order × resep
 *
 * Idempoten — aman dijalankan ulang.
 */
class RecipeIngredientSeeder extends Seeder
{
    // ── Definisi 24 bahan baku ─────────────────────────────────────────────
    private function ingredientDefs(): array
    {
        return [
            // Nama                  Unit     Threshold  Stok awal  Cost/unit(Rp)
            ['Bubuk Kopi',    'gram',  1000,   10000,   200],
            ['Susu Cair',     'ml',    5000,  100000,    15],
            ['Sirup Gula',    'ml',    1000,   20000,    10],
            ['Teh Celup',     'pcs',    100,    3000,   300],
            ['Krimer',        'gram',   500,   15000,    50],
            ['Tepung Terigu', 'gram',  1000,   30000,    12],
            ['Telur Ayam',    'pcs',     50,    1000,  2500],
            ['Minyak Goreng', 'ml',    2000,   30000,    25],
            ['Bawang Merah',  'gram',   500,   15000,    30],
            ['Garam',         'gram',   300,   10000,     5],
            ['Gula Pasir',    'gram',  2000,   50000,    15],
            ['Coklat Bubuk',  'gram',   500,   10000,   100],
            ['Mentega',       'gram',   300,    8000,    80],
            ['Keju Parut',    'gram',   300,    6000,   120],
            ['Sirup Vanila',  'ml',     300,    8000,   100],
            ['Bubuk Matcha',  'gram',   200,    5000,   300],
            ['Jeruk Nipis',   'pcs',     50,     500,  1000],
            ['Pisang',        'pcs',     50,    1000,  1500],
            ['Tempe',         'pcs',     30,     500,  2500],
            ['Kentang',       'kg',       5,     100, 20000],
            ['Mie',           'gram',  1000,   50000,    15],
            ['Nasi',          'gram',  3000,  200000,     5],
            ['Ayam',          'gram',  1000,   30000,    80],
            ['Kecap Manis',   'ml',     500,   15000,    25],
        ];
    }

    // ── Resep per menu (bahan_nama => qty per 1 porsi) ────────────────────
    private function recipeDefs(): array
    {
        return [
            'Espresso' => [
                'Bubuk Kopi' => 10,
            ],
            'Americano Panas' => [
                'Bubuk Kopi' => 10,
            ],
            'Es Americano' => [
                'Bubuk Kopi' => 10,
                'Sirup Gula' => 20,
            ],
            'Kopi Susu' => [
                'Bubuk Kopi' => 10,
                'Susu Cair'  => 150,
                'Sirup Gula' => 25,
            ],
            'Teh Tawar' => [
                'Teh Celup' => 1,
            ],
            'Teh Manis' => [
                'Teh Celup'  => 1,
                'Gula Pasir' => 15,
            ],
            'Teh Susu' => [
                'Teh Celup'  => 1,
                'Susu Cair'  => 100,
                'Gula Pasir' => 10,
            ],
            'Jeruk Nipis' => [
                'Jeruk Nipis' => 2,
                'Gula Pasir'  => 20,
            ],
            'Teh Jeruk (Lime Tea)' => [
                'Teh Celup'   => 1,
                'Jeruk Nipis' => 1,
                'Gula Pasir'  => 15,
            ],
            'Full Chocolate' => [
                'Coklat Bubuk' => 20,
                'Susu Cair'    => 150,
                'Gula Pasir'   => 15,
            ],
            'Matcha' => [
                'Bubuk Matcha' => 5,
                'Susu Cair'    => 150,
                'Gula Pasir'   => 15,
            ],
            'Vanilla Latte' => [
                'Bubuk Kopi'  => 10,
                'Susu Cair'   => 150,
                'Sirup Vanila' => 15,
                'Gula Pasir'  => 5,
            ],
            'Creamy Chocolatey' => [
                'Coklat Bubuk' => 20,
                'Susu Cair'    => 100,
                'Krimer'       => 30,
                'Gula Pasir'   => 15,
            ],
            'Pisang Coklat Keju' => [
                'Pisang'       => 2,
                'Coklat Bubuk' => 10,
                'Keju Parut'   => 20,
                'Tepung Terigu' => 30,
                'Mentega'      => 10,
            ],
            'Tempe Mendoan' => [
                'Tempe'        => 1,
                'Tepung Terigu' => 50,
                'Bawang Merah' => 10,
                'Garam'        => 3,
                'Minyak Goreng' => 30,
            ],
            'Kentang (French Fries)' => [
                'Kentang'      => 0.15,
                'Garam'        => 3,
                'Minyak Goreng' => 50,
            ],
            'Mie Goreng Telur' => [
                'Mie'          => 85,
                'Telur Ayam'   => 1,
                'Minyak Goreng' => 15,
                'Bawang Merah' => 10,
                'Garam'        => 2,
            ],
            'Mie Rebus Telur' => [
                'Mie'          => 85,
                'Telur Ayam'   => 1,
                'Bawang Merah' => 5,
                'Garam'        => 2,
            ],
            'Nasgor Telur' => [
                'Nasi'         => 200,
                'Telur Ayam'   => 1,
                'Minyak Goreng' => 20,
                'Bawang Merah' => 15,
                'Garam'        => 3,
                'Kecap Manis'  => 10,
            ],
            'Nasgor Ayam/Udang' => [
                'Nasi'         => 200,
                'Telur Ayam'   => 1,
                'Ayam'         => 100,
                'Minyak Goreng' => 20,
                'Bawang Merah' => 15,
                'Garam'        => 3,
                'Kecap Manis'  => 10,
            ],
            'Nasi Telur Saus' => [
                'Nasi'         => 200,
                'Telur Ayam'   => 2,
                'Minyak Goreng' => 15,
                'Garam'        => 3,
            ],
            'Nasi Telur Kecap' => [
                'Nasi'         => 200,
                'Telur Ayam'   => 2,
                'Kecap Manis'  => 15,
                'Minyak Goreng' => 10,
                'Garam'        => 3,
            ],
            'Nasi Ayam Geprek' => [
                'Nasi'         => 200,
                'Ayam'         => 150,
                'Minyak Goreng' => 50,
                'Bawang Merah' => 15,
                'Garam'        => 5,
                'Tepung Terigu' => 30,
            ],
        ];
    }

    // ────────────────────────────────────────────────────────────────────────
    public function run(): void
    {
        DB::statement('SET session_replication_role = replica');  // disable FK checks (PostgreSQL)

        // ── Bersihkan tabel yang akan diisi ulang ─────────────────────────
        DB::table('daily_ingredient_usages')->truncate();
        DB::table('menu_ingredients')->truncate();
        DB::table('ingredient_batches')->truncate();

        $now = now()->toDateTimeString();

        // ════════════════════════════════════════════════════════════════════
        // TAHAP 1: Upsert bahan baku
        // ════════════════════════════════════════════════════════════════════
        $this->command->info('Tahap 1: Upsert bahan baku...');

        $ingredientIds = [];  // name → id

        foreach ($this->ingredientDefs() as [$name, $unit, $threshold]) {
            // Cari bahan yang sudah ada (non-soft-deleted)
            $existing = DB::table('ingredients')
                ->whereNull('deleted_at')
                ->where('name', $name)
                ->first();

            if ($existing) {
                DB::table('ingredients')
                    ->where('id', $existing->id)
                    ->update([
                        'unit'                => $unit,
                        'low_stock_threshold' => $threshold,
                        'is_active'           => true,
                        'updated_at'          => $now,
                    ]);
                $ingredientIds[$name] = $existing->id;
            } else {
                $id = DB::table('ingredients')->insertGetId([
                    'name'                => $name,
                    'unit'                => $unit,
                    'low_stock_threshold' => $threshold,
                    'is_active'           => true,
                    'created_at'          => $now,
                    'updated_at'          => $now,
                ]);
                $ingredientIds[$name] = $id;
            }
        }

        $this->command->info('  → ' . count($ingredientIds) . ' bahan baku siap.');

        // ════════════════════════════════════════════════════════════════════
        // TAHAP 2: Tambah stok awal (ingredient_batches) + cost/unit
        // ════════════════════════════════════════════════════════════════════
        $this->command->info('Tahap 2: Tambah stok awal (ingredient_batches)...');

        $batches = [];
        foreach ($this->ingredientDefs() as [$name, $unit, $threshold, $stock, $cost]) {
            if (! isset($ingredientIds[$name])) {
                continue;
            }
            $batches[] = [
                'ingredient_id' => $ingredientIds[$name],
                'quantity'      => $stock,
                'expiry_date'   => null,
                'received_at'   => $now,
                'cost_per_unit' => $cost,
            ];
        }

        DB::table('ingredient_batches')->insert($batches);
        $this->command->info('  → ' . count($batches) . ' batch stok ditambahkan.');

        // ════════════════════════════════════════════════════════════════════
        // TAHAP 3: Buat resep menu (menu_ingredients)
        // ════════════════════════════════════════════════════════════════════
        $this->command->info('Tahap 3: Buat resep menu...');

        // Ambil semua menu id (menus tidak punya soft delete)
        $menuIds = DB::table('menus')
            ->pluck('id', 'name');

        $recipes  = [];
        $hasRecipe = [];
        foreach ($this->recipeDefs() as $menuName => $ingredients) {
            if (! isset($menuIds[$menuName])) {
                $this->command->warn("  ⚠ Menu tidak ditemukan: {$menuName}");
                continue;
            }
            $menuId = $menuIds[$menuName];
            foreach ($ingredients as $ingName => $qty) {
                if (! isset($ingredientIds[$ingName])) {
                    $this->command->warn("  ⚠ Bahan tidak ditemukan: {$ingName}");
                    continue;
                }
                $recipes[] = [
                    'menu_id'       => $menuId,
                    'ingredient_id' => $ingredientIds[$ingName],
                    'quantity_used' => $qty,
                ];
            }
            $hasRecipe[] = $menuId;
        }

        DB::table('menu_ingredients')->insert($recipes);

        // Update is_stock_calculated pada menu yang punya resep
        DB::table('menus')->whereIn('id', $hasRecipe)->update(['is_stock_calculated' => true]);
        DB::table('menus')->whereNotIn('id', $hasRecipe)
            ->update(['is_stock_calculated' => false]);

        $this->command->info('  → ' . count($this->recipeDefs()) . ' menu dibuatkan resep, ' . count($recipes) . ' baris menu_ingredients.');

        // ════════════════════════════════════════════════════════════════════
        // TAHAP 4: Hitung daily_ingredient_usages dari order nyata × resep
        // ════════════════════════════════════════════════════════════════════
        $this->command->info('Tahap 4: Hitung pemakaian bahan baku harian dari riwayat pesanan...');

        // Query agregasi: per tanggal per bahan, total penggunaan
        $usageRows = DB::select("
            SELECT
                o.created_at::date          AS usage_date,
                mi.ingredient_id,
                SUM(oi.quantity * mi.quantity_used)::numeric(12,2) AS jumlah_digunakan
            FROM orders o
            JOIN order_items oi ON oi.order_id = o.id
            JOIN menu_ingredients mi ON mi.menu_id = oi.menu_id
            WHERE o.status = 'selesai'
            GROUP BY o.created_at::date, mi.ingredient_id
            ORDER BY o.created_at::date, mi.ingredient_id
        ");

        if (empty($usageRows)) {
            $this->command->warn('  ⚠ Tidak ada data pesanan selesai yang bisa dihitung. Pastikan PredictionHistorySeeder sudah dijalankan.');
            DB::statement('SET session_replication_role = DEFAULT');
            return;
        }

        // Ambil nama & unit semua bahan (untuk denormalisasi)
        $ingMeta = DB::table('ingredients')
            ->whereNull('deleted_at')
            ->select('id', 'name', 'unit')
            ->get()
            ->keyBy('id');

        $insertRows = [];
        foreach ($usageRows as $row) {
            $meta = $ingMeta->get($row->ingredient_id);
            if (! $meta) {
                continue;
            }
            $insertRows[] = [
                'usage_date'       => $row->usage_date,
                'ingredient_id'    => $row->ingredient_id,
                'ingredient_name'  => $meta->name,
                'unit'             => $meta->unit,
                'jumlah_digunakan' => (float) $row->jumlah_digunakan,
                'created_at'       => $now,
                'updated_at'       => $now,
            ];
        }

        foreach (array_chunk($insertRows, 500) as $chunk) {
            DB::table('daily_ingredient_usages')->insert($chunk);
        }

        DB::statement('SET session_replication_role = DEFAULT');  // restore FK checks

        $this->command->info(
            '  → ' . count($insertRows) . ' baris daily_ingredient_usages dihitung dari ' .
            count($usageRows) . ' kombinasi (tanggal × bahan).'
        );

        $this->command->info('RecipeIngredientSeeder selesai.');
    }
}
