<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TransaksiHistorySeeder extends Seeder
{
    // Menu IDs sesuai data Excel (tidak termasuk "Data Mining Seed" menu 24-28)
    private array $eligibleMenuIds = [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23];

    private array $menuPrices    = [];
    private array $weightedAnchors = [];

    /**
     * Peta anchor → companion untuk mendukung association rules.
     * Pasangan yang didefinisikan di sini akan sering muncul bersama di transaksi.
     *
     * Target asosiasi kuat:
     *  Kopi Susu       ↔ Pisang Coklat Keju / Tempe Mendoan
     *  Mie Goreng Telur ↔ Kopi Susu / Es Americano
     *  Nasi Ayam Geprek ↔ Es Americano / Jeruk Nipis
     *  Nasgor Ayam/Udang ↔ Teh Manis / Jeruk Nipis
     *  Kentang         ↔ Americano Panas / Espresso
     *  Pisang Coklat Keju ↔ Tempe Mendoan / Kopi Susu
     *  Mie Rebus Telur  ↔ Teh Susu / Teh Manis
     */
    private array $companionMap = [
        // ── Coffee ────────────────────────────────────────────────────────
        4  => [14, 15, 17, 19, 20],  // Kopi Susu       → Pisang Coklat Keju, Tempe Mendoan, Mie Goreng Telur, Nasgor
        3  => [23, 22, 20, 17, 8],   // Es Americano    → Nasi Ayam Geprek, Nasi Telur Kecap, Nasgor, Jeruk Nipis
        2  => [16, 15, 18, 1, 14],   // Americano Panas → Kentang, Tempe Mendoan, Mie Rebus Telur, Espresso
        1  => [16, 2, 15, 14],       // Espresso        → Kentang, Americano Panas, Tempe, Pisang Coklat Keju
        // ── Tea / Lime ────────────────────────────────────────────────────
        7  => [18, 14, 15, 21],      // Teh Susu        → Mie Rebus Telur, snack, Nasi Telur Saus
        6  => [20, 19, 21, 22],      // Teh Manis       → Nasgor Ayam/Udang, Nasgor Telur, nasi
        9  => [23, 22, 20, 8],       // Teh Jeruk       → Nasi Ayam Geprek, Nasi Telur Kecap, Nasgor, Jeruk Nipis
        8  => [20, 19, 9, 22],       // Jeruk Nipis     → Nasgor Ayam/Udang, Nasgor Telur, Teh Jeruk
        5  => [18, 21, 19, 22],      // Teh Tawar       → Mie Rebus Telur, nasi
        // ── Chocolatey ───────────────────────────────────────────────────
        10 => [14, 15, 4, 16],       // Full Chocolate  → Pisang Coklat Keju, Tempe, Kopi Susu, Kentang
        11 => [14, 15, 16, 4],       // Matcha          → Pisang Coklat Keju, Tempe, Kentang, Kopi Susu
        12 => [14, 15, 16, 13],      // Vanilla Latte   → Pisang Coklat Keju, Tempe, Kentang
        13 => [14, 15, 16, 12],      // Creamy Chocolatey → Pisang Coklat Keju, Tempe, Kentang
        // ── Snack ────────────────────────────────────────────────────────
        14 => [15, 4, 10, 11],       // Pisang Coklat Keju → Tempe Mendoan, Kopi Susu, Chocolatey
        15 => [14, 4, 16, 2],        // Tempe Mendoan   → Pisang Coklat Keju, Kopi Susu, Kentang
        16 => [2, 1, 4, 15],         // Kentang         → Americano Panas, Espresso, Kopi Susu, Tempe
        // ── Noodle ───────────────────────────────────────────────────────
        17 => [4, 3, 7, 9],          // Mie Goreng Telur → Kopi Susu, Es Americano, Teh Susu, Teh Jeruk
        18 => [7, 6, 4, 5],          // Mie Rebus Telur  → Teh Susu, Teh Manis, Kopi Susu, Teh Tawar
        // ── Nasgor ───────────────────────────────────────────────────────
        19 => [4, 7, 6, 3],          // Nasgor Telur       → Kopi Susu, Teh Susu, Teh Manis, Es Americano
        20 => [6, 8, 9, 7],          // Nasgor Ayam/Udang  → Teh Manis, Jeruk Nipis, Teh Jeruk, Teh Susu
        // ── Nasi ─────────────────────────────────────────────────────────
        21 => [9, 6, 4, 7],          // Nasi Telur Saus  → Teh Jeruk, Teh Manis, Kopi Susu, Teh Susu
        22 => [3, 8, 6, 4],          // Nasi Telur Kecap → Es Americano, Jeruk Nipis, Teh Manis, Kopi Susu
        23 => [3, 4, 8, 9],          // Nasi Ayam Geprek → Es Americano, Kopi Susu, Jeruk Nipis, Teh Jeruk
    ];

    // Bobot popularitas menu (lebih besar = lebih sering muncul sebagai anchor)
    private array $menuWeights = [
        4  => 15,  // Kopi Susu – sangat populer
        3  => 12,  // Es Americano
        17 => 10,  // Mie Goreng Telur
        23 => 10,  // Nasi Ayam Geprek
        20 => 9,   // Nasgor Ayam/Udang
        14 => 9,   // Pisang Coklat Keju
        2  => 8,   // Americano Panas
        18 => 8,   // Mie Rebus Telur
        19 => 7,   // Nasgor Telur
        7  => 7,   // Teh Susu
        6  => 7,   // Teh Manis
        15 => 6,   // Tempe Mendoan
        16 => 6,   // Kentang (French Fries)
        22 => 6,   // Nasi Telur Kecap
        21 => 5,   // Nasi Telur Saus
        10 => 5,   // Full Chocolate
        9  => 5,   // Teh Jeruk (Lime Tea)
        8  => 5,   // Jeruk Nipis
        11 => 4,   // Matcha
        12 => 4,   // Vanilla Latte
        13 => 4,   // Creamy Chocolatey
        1  => 4,   // Espresso
        5  => 3,   // Teh Tawar
    ];

    public function run(): void
    {
        set_time_limit(600);
        DB::disableQueryLog();

        // ── Hapus data lama ────────────────────────────────────────────────
        $this->command->info('Membersihkan data pesanan lama...');
        DB::statement('TRUNCATE TABLE orders RESTART IDENTITY CASCADE');

        // ── Muat harga menu dari DB ───────────────────────────────────────
        $this->menuPrices = DB::table('menus')
            ->whereIn('id', $this->eligibleMenuIds)
            ->pluck('price', 'id')
            ->map(fn ($p) => (float) $p)
            ->toArray();

        $this->eligibleMenuIds = array_keys($this->menuPrices);

        // ── Bangun array anchor berbobot ──────────────────────────────────
        foreach ($this->menuWeights as $menuId => $weight) {
            if (isset($this->menuPrices[$menuId])) {
                for ($i = 0; $i < $weight; $i++) {
                    $this->weightedAnchors[] = $menuId;
                }
            }
        }

        // ── Cari ID kasir/admin ───────────────────────────────────────────
        $cashierId = DB::table('users')
            ->whereIn('role', ['cashier', 'admin'])
            ->orderByRaw("CASE WHEN role = 'cashier' THEN 0 ELSE 1 END")
            ->value('id');

        // ── Loop per bulan 2023-01 s.d. 2025-12 ──────────────────────────
        $this->command->info('Mulai membuat data transaksi 2023–2025...');
        $totalOrders = 0;

        $current = Carbon::parse('2023-01-01');
        $endDate = Carbon::parse('2025-12-31');

        while ($current->lte($endDate)) {
            $count = $this->processMonth($current->copy(), $cashierId);
            $totalOrders += $count;
            $this->command->info("  ✓ {$current->format('Y-m')}: {$count} pesanan");
            $current->addMonth();
        }

        $this->command->info("\n✓ Selesai! Total {$totalOrders} pesanan berhasil dibuat (2023–2025).");
    }

    // ── Proses satu bulan ──────────────────────────────────────────────────
    private function processMonth(Carbon $monthStart, ?int $cashierId): int
    {
        $day      = $monthStart->startOfMonth()->copy();
        $monthEnd = $monthStart->copy()->endOfMonth();

        $ordersBatch = [];
        $itemsByCode = [];

        while ($day->lte($monthEnd)) {
            // Tidak ada penjualan di hari Minggu
            if ($day->dayOfWeek === Carbon::SUNDAY) {
                $day->addDay();
                continue;
            }

            $isSaturday   = ($day->dayOfWeek === Carbon::SATURDAY);
            $ordersToday  = $this->ordersPerDay($day->month, $isSaturday);
            $dateKey      = $day->format('Ymd');

            for ($seq = 1; $seq <= $ordersToday; $seq++) {
                $orderCode = 'ORD-' . $dateKey . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
                $orderTime = $day->copy()->setTime($this->randomHour(), rand(0, 59), rand(0, 59));
                $items     = $this->generateItems();
                $total     = array_sum(array_column($items, 'subtotal'));

                $ordersBatch[] = [
                    'order_code'     => $orderCode,
                    'customer_name'  => null,
                    'customer_phone' => null,
                    'table_id'       => null,
                    'cashier_id'     => $cashierId,
                    'status'         => 'selesai',
                    'order_type'     => 'cashier',
                    'payment_method' => rand(0, 9) < 8 ? 'cash' : 'qris',
                    'payment_proof'  => null,
                    'rejection_note' => null,
                    'is_paid'        => true,
                    'total_amount'   => $total,
                    'notes'          => null,
                    'created_at'     => $orderTime->toDateTimeString(),
                    'updated_at'     => $orderTime->toDateTimeString(),
                ];

                $itemsByCode[$orderCode] = [
                    'time'  => $orderTime->toDateTimeString(),
                    'items' => $items,
                ];
            }

            $day->addDay();
        }

        if (empty($ordersBatch)) {
            return 0;
        }

        // Insert orders dalam chunk 500
        foreach (array_chunk($ordersBatch, 500) as $chunk) {
            DB::table('orders')->insert($chunk);
        }

        // Ambil kembali ID yang baru di-insert
        $codes    = array_column($ordersBatch, 'order_code');
        $orderIds = DB::table('orders')
            ->whereIn('order_code', $codes)
            ->pluck('id', 'order_code')
            ->toArray();

        // Bangun dan insert order_items
        $itemsBatch = [];
        foreach ($itemsByCode as $code => $data) {
            $orderId = $orderIds[$code] ?? null;
            if (! $orderId) {
                continue;
            }
            foreach ($data['items'] as $pos => $item) {
                $itemsBatch[] = [
                    'order_id'      => $orderId,
                    'item_position' => $pos + 1,   // 1-based: first item = 1
                    'menu_id'       => $item['menu_id'],
                    'quantity'      => $item['quantity'],
                    'unit_price'    => $item['unit_price'],
                    'subtotal'      => $item['subtotal'],
                    'notes'         => null,
                    'created_at'    => $data['time'],
                    'updated_at'    => $data['time'],
                ];
            }
        }

        foreach (array_chunk($itemsBatch, 1000) as $chunk) {
            DB::table('order_items')->insert($chunk);
        }

        return count($ordersBatch);
    }

    // ── Jumlah pesanan per hari ────────────────────────────────────────────
    private function ordersPerDay(int $month, bool $isSaturday): int
    {
        if ($isSaturday) {
            return rand(8, 14);
        }

        $base = rand(15, 25);

        // Puncak semester: Maret-April & Oktober-November
        if (in_array($month, [3, 4, 10, 11])) {
            return (int) round($base * 1.15);
        }
        // Sepi: Juli (libur tengah tahun) & Desember (libur akhir tahun)
        if (in_array($month, [7, 12])) {
            return (int) round($base * 0.85);
        }

        return $base;
    }

    // ── Buat item-item dalam satu pesanan ─────────────────────────────────
    private function generateItems(): array
    {
        $usedIds = [];

        if (rand(0, 9) < 7) {
            // 70 % — pola anchor + companion (untuk association rules)
            $anchorId  = $this->weightedAnchors[array_rand($this->weightedAnchors)];
            $usedIds[] = $anchorId;

            $companions = $this->companionMap[$anchorId] ?? [];
            shuffle($companions);
            $numComp = rand(0, 2);

            foreach (array_slice($companions, 0, $numComp) as $comp) {
                if (! in_array($comp, $usedIds) && isset($this->menuPrices[$comp])) {
                    $usedIds[] = $comp;
                }
                if (count($usedIds) >= 3) {
                    break;
                }
            }
        } else {
            // 30 % — acak
            $pool    = $this->eligibleMenuIds;
            shuffle($pool);
            $usedIds = array_slice($pool, 0, rand(1, 3));
        }

        $items = [];
        foreach ($usedIds as $menuId) {
            $price = $this->menuPrices[$menuId] ?? 0.0;
            if ($price <= 0) {
                continue;
            }
            // 80 % qty=1, 20 % qty=2
            $qty     = rand(0, 4) < 4 ? 1 : 2;
            $items[] = [
                'menu_id'    => $menuId,
                'quantity'   => $qty,
                'unit_price' => $price,
                'subtotal'   => $price * $qty,
            ];
        }

        // Fallback agar tidak kosong
        if (empty($items)) {
            $mid     = $this->eligibleMenuIds[array_rand($this->eligibleMenuIds)];
            $price   = $this->menuPrices[$mid] ?? 10000.0;
            $items[] = ['menu_id' => $mid, 'quantity' => 1, 'unit_price' => $price, 'subtotal' => $price];
        }

        return $items;
    }

    // ── Jam operasional dengan distribusi bobot ───────────────────────────
    private function randomHour(): int
    {
        // Puncak: 10-12 (istirahat kuliah) dan 15-18 (sore)
        $weighted = [
            7 => 3, 8 => 4, 9 => 5,
            10 => 9, 11 => 10, 12 => 9,
            13 => 5, 14 => 5,
            15 => 8, 16 => 9, 17 => 8,
            18 => 5, 19 => 4, 20 => 3,
        ];

        $pool = [];
        foreach ($weighted as $hour => $weight) {
            for ($i = 0; $i < $weight; $i++) {
                $pool[] = $hour;
            }
        }

        return $pool[array_rand($pool)];
    }
}
