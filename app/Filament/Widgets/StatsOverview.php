<?php

namespace App\Filament\Widgets;

use App\Models\Menu;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // 1 query: ambil penjualan 7 hari terakhir sekaligus
        $salesByDay = Order::where('status', Order::STATUS_SELESAI)
            ->whereBetween(DB::raw('created_at::date'), [today()->subDays(6)->toDateString(), today()->toDateString()])
            ->selectRaw("created_at::date as day, SUM(total_amount) as total")
            ->groupBy('day')
            ->pluck('total', 'day');

        $last7Days = [];
        for ($i = 6; $i >= 0; $i--) {
            $last7Days[] = (float) ($salesByDay[today()->subDays($i)->toDateString()] ?? 0);
        }

        $salesToday     = $last7Days[6];
        $salesYesterday = $last7Days[5];
        $salesChange    = $salesYesterday > 0
            ? round((($salesToday - $salesYesterday) / $salesYesterday) * 100, 1)
            : 0;

        // 1 query: count menu + categories
        $menuStats = Menu::where('is_available', true)
            ->selectRaw('COUNT(*) as total_menu, COUNT(DISTINCT category_id) as total_categories')
            ->first();

        // 1 query: orders bulan ini & bulan lalu sekaligus
        $ordersPerMonth = Order::selectRaw(
            "DATE_TRUNC('month', created_at) as month, COUNT(*) as total"
        )
            ->whereIn(DB::raw("DATE_TRUNC('month', created_at)"), [
                today()->startOfMonth()->toDateString(),
                today()->subMonth()->startOfMonth()->toDateString(),
            ])
            ->groupBy('month')
            ->pluck('total', 'month')
            ->mapWithKeys(fn ($v, $k) => [substr($k, 0, 7) => $v]);

        $ordersThisMonth = $ordersPerMonth[today()->format('Y-m')] ?? 0;
        $ordersLastMonth = $ordersPerMonth[today()->subMonth()->format('Y-m')] ?? 0;
        $ordersChange    = $ordersLastMonth > 0
            ? round((($ordersThisMonth - $ordersLastMonth) / $ordersLastMonth) * 100, 1)
            : 0;

        return [
            Stat::make('Penjualan Hari Ini', 'Rp ' . number_format($salesToday, 0, ',', '.'))
                ->description(($salesChange >= 0 ? '↑ ' : '↓ ') . abs($salesChange) . '% dari kemarin')
                ->descriptionIcon($salesChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($salesChange >= 0 ? 'success' : 'danger')
                ->chart($last7Days),

            Stat::make('Menu Tersedia', $menuStats->total_menu ?? 0)
                ->description(($menuStats->total_categories ?? 0) . ' kategori aktif')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info'),

            Stat::make('Pesanan Bulan Ini', $ordersThisMonth)
                ->description(($ordersChange >= 0 ? '↑ ' : '↓ ') . abs($ordersChange) . '% dari bulan lalu')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('primary'),
        ];
    }
}
