<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Menu;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        Menu::truncate();

        $coffee   = Category::where('slug', 'coffee-base')->value('id');
        $tea      = Category::where('slug', 'tea-base')->value('id');
        $lime     = Category::where('slug', 'lime-base')->value('id');
        $choco    = Category::where('slug', 'chocolatos-base')->value('id');
        $snack    = Category::where('slug', 'snack')->value('id');
        $indomie  = Category::where('slug', 'indomie-base')->value('id');
        $nasgor   = Category::where('slug', 'nasi-goreng')->value('id');
        $nastel   = Category::where('slug', 'nasi-telur')->value('id');
        $geprek   = Category::where('slug', 'ayam-geprek')->value('id');

        // [category_id, name, price, cashback]
        $menus = [
            // ── COFFEE BASE ────────────────────────────────────
            [$coffee, 'Espresso',              10000, 2000],
            [$coffee, 'Americano Panas',       10000, 2000],
            [$coffee, 'Es Americano',          12000, 2000],
            [$coffee, 'Kopi Susu',             14000, 2000],

            // ── TEA BASE ───────────────────────────────────────
            [$tea,    'Teh Tawar',              3000, 1000],
            [$tea,    'Teh Manis',              4000, 1000],
            [$tea,    'Teh Susu',               7000, 2000],

            // ── LIME BASE ──────────────────────────────────────
            [$lime,   'Jeruk Nipis',            5000, 1000],
            [$lime,   'Teh Jeruk (Lime Tea)',   6000, 1000],

            // ── CHOCOLATOS BASE ───────────────────────────────
            [$choco,  'Full Chocolate',         8000, 2000],
            [$choco,  'Matcha',                 8000, 2000],
            [$choco,  'Vanilla Latte',          8000, 2000],
            [$choco,  'Creamy Chocolatey',      8000, 2000],

            // ── SNACK ─────────────────────────────────────────
            [$snack,  'Pisang Coklat Keju',    10000, 2000],
            [$snack,  'Tempe Mendoan',          8000, 2000],
            [$snack,  'Kentang (French Fries)', 12000, 2000],

            // ── INDOMIE BASE ──────────────────────────────────
            [$indomie,'Mie Goreng Telur',      10000, 1000],
            [$indomie,'Mie Rebus Telur',       10000, 1000],

            // ── NASI GORENG ───────────────────────────────────
            [$nasgor, 'Nasgor Telur',          12000, 2000],
            [$nasgor, 'Nasgor Ayam/Udang',     17000, 2000],

            // ── NASI TELUR ────────────────────────────────────
            [$nastel, 'Nasi Telur Saus',        9000, 1000],
            [$nastel, 'Nasi Telur Kecap',       8000, 1000],

            // ── AYAM GEPREK ───────────────────────────────────
            [$geprek, 'Nasi Ayam Geprek',      14000, 2000],
        ];

        foreach ($menus as [$catId, $name, $price, $cashback]) {
            Menu::create([
                'category_id'         => $catId,
                'name'                => $name,
                'description'         => null,
                'price'               => $price,
                'cashback'            => $cashback,
                'image'               => null,
                'is_available'        => true,
                'is_student_discount' => true,
                'student_price'       => $price - $cashback,
            ]);
        }
    }
}
