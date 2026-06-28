<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;

class HargaModalSeeder extends Seeder
{
    /**
     * Harga modal per menu berdasarkan data implementasi W9 Cafe.
     * Mapping dilakukan berdasarkan nama menu (case-insensitive).
     */
    private array $hargaModalMap = [
        'espresso'               => 2500,
        'americano panas'        => 2500,
        'es americano'           => 7200,
        'kopi susu'              => 3000,
        'teh tawar'              => 1000,
        'teh manis'              => 2400,
        'teh susu'               => 2500,
        'jeruk nipis'            => 2000,
        'teh jeruk (lime tea)'   => 2500,
        'full chocolate'         => 4800,
        'matcha'                 => 4800,
        'vanilla latte'          => 4800,
        'creamy chocolatey'      => 4800,
        'pisang coklat keju'     => 5000,
        'tempe mendoan'          => 4800,
        'kentang (french fries)' => 7200,
        'mie goreng telur'       => 8000,
        'mie rebus telur'        => 8000,
        'nasgor telur'           => 7200,
        'nasgor ayam/udang'      => 11000,
        'nasi telur saus'        => 6000,
        'nasi telur kecap'       => 5500,
        'nasi ayam geprek'       => 8400,
    ];

    public function run(): void
    {
        $menus = Menu::all();
        $updated = 0;

        foreach ($menus as $menu) {
            $key = strtolower(trim($menu->name));

            if (isset($this->hargaModalMap[$key])) {
                $menu->update(['harga_modal' => $this->hargaModalMap[$key]]);
                $updated++;
                $this->command->info("✓ {$menu->name} → Rp " . number_format($this->hargaModalMap[$key], 0, ',', '.'));
            } else {
                $this->command->warn("  {$menu->name} → tidak ada di mapping, dilewati (harga_modal tetap 0)");
            }
        }

        $this->command->info("\nSelesai: {$updated} menu diperbarui.");
    }
}
