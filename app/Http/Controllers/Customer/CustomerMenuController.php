<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\CafeTable;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class CustomerMenuController extends Controller
{
    private function findTable(?string $tableId): ?CafeTable
    {
        if (!$tableId) return null;

        return Cache::remember("cafe_table_{$tableId}", 600, fn () =>
            CafeTable::select(['id', 'table_number', 'is_available'])->find($tableId)
        );
    }

    public function showIdentitas(Request $request)
    {
        $table = $this->findTable($request->query('table'));

        // Tolak jika meja tidak ada di DB atau ditandai tidak tersedia
        if ($tableId = $request->query('table')) {
            if (! $table || ! $table->is_available) {
                abort(404);
            }
        }

        return Inertia::render('Pelanggan/Identitas', ['table' => $table]);
    }

    public function index(Request $request)
    {
        $categories = Cache::remember('customer_menu_v3', 300, function () {
            return Category::with([
                'menus' => fn ($q) => $q
                    ->select(['id', 'category_id', 'name', 'price', 'student_price', 'image', 'is_available'])
                    ->orderBy('name'),
            ])
                ->select(['id', 'name'])
                ->orderBy('name')
                ->get();
        });

        $table = $this->findTable($request->query('table'));

        if ($tableId = $request->query('table')) {
            if (! $table || ! $table->is_available) {
                abort(404);
            }
        }

        return Inertia::render('Pelanggan/Menu/Index', compact('categories', 'table'));
    }
}
