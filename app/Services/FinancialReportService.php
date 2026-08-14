<?php

namespace App\Services;

use App\DTO\ReportData;
use App\Models\Category;
use App\Models\Expense;
use App\Models\IngredientBatch;
use App\Models\Order;
use App\Models\Receivable;
use App\Models\UnexpectedTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FinancialReportService
{
    /**
     * Generate a financial report.
     *
     * @param  string  $type  'simple', 'rigid', or 'custom'
     * @param  array  $params  [
     *                         'date_start'  => string|null,
     *                         'date_end'    => string|null,
     *                         'categories'  => array|null,
     *                         'aggregation' => 'daily'|'monthly',
     *                         ]
     *
     * @throws \InvalidArgumentException for unknown report types
     */
    public function generate(string $type, array $params = []): ReportData
    {
        // Validate date ordering before dispatching to report type
        if (isset($params['date_start']) && isset($params['date_end'])) {
            $dateStart = Carbon::parse($params['date_start']);
            $dateEnd = Carbon::parse($params['date_end']);

            if ($dateStart->greaterThan($dateEnd)) {
                throw new \InvalidArgumentException('date_start cannot be after date_end');
            }
        }

        return match ($type) {
            'simple' => $this->generateSimple($params),
            'rigid' => $this->generateRigid($params),
            'custom' => $this->generateCustom($params),
            default => throw new \InvalidArgumentException("Unknown report type: {$type}"),
        };
    }

    // ─── Simple Report ─────────────────────────────────────────────────

    /**
     * Generate a simple financial report for the given date range.
     * Consolidates income from paid orders + unexpected pemasukan,
     * and expenses from ingredient batches + unexpected pengeluaran.
     */
    private function generateSimple(array $params): ReportData
    {
        $start = isset($params['date_start'])
            ? Carbon::parse($params['date_start'])->startOfDay()
            : Carbon::createFromDate(2000, 1, 1)->startOfDay();

        $end = isset($params['date_end'])
            ? Carbon::parse($params['date_end'])->endOfDay()
            : Carbon::now()->endOfDay();

        $dateStart = $start->toDateString();
        $dateEnd = $end->toDateString();

        $totalIncome = $this->calcTotalIncome($start, $end);
        $totalExpense = $this->calcTotalExpense($start, $end);
        $net = $totalIncome - $totalExpense;

        $data = [
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'net' => $net,
            'income_breakdown' => $this->calcIncomeBreakdown($start, $end),
            'expense_breakdown' => $this->calcExpenseBreakdown($start, $end),
            'receivables_outstanding' => $this->calcReceivablesOutstanding($start, $end),
            'date_range' => [
                'start' => $dateStart,
                'end' => $dateEnd,
            ],
        ];

        return ReportData::fromSimpleReport($data, $dateStart, $dateEnd);
    }

    private function calcTotalIncome(Carbon $start, Carbon $end): float
    {
        $fromOrders = (float) Order::where('payment_method', '!=', 'bayar_nanti')
            ->whereBetween('created_at', [$start, $end])
            ->sum('total_amount');

        $fromUnexpected = (float) UnexpectedTransaction::where('jenis', 'pemasukan')
            ->whereBetween('created_at', [$start, $end])
            ->sum('nominal');

        return $fromOrders + $fromUnexpected;
    }

    private function calcTotalExpense(Carbon $start, Carbon $end): float
    {
        $fromExpenses = (float) Expense::whereBetween('date', [$start, $end])
            ->sum('amount');

        $fromBatches = (float) IngredientBatch::whereBetween('received_at', [$start, $end])
            ->selectRaw('SUM(quantity * cost_per_unit) as total')
            ->value('total') ?? 0;

        $fromUnexpected = (float) UnexpectedTransaction::where('jenis', 'pengeluaran')
            ->whereBetween('created_at', [$start, $end])
            ->sum('nominal');

        return $fromExpenses + $fromBatches + $fromUnexpected;
    }

    private function calcIncomeBreakdown(Carbon $start, Carbon $end): array
    {
        $ordersByPayment = Order::where('payment_method', '!=', 'bayar_nanti')
            ->whereBetween('created_at', [$start, $end])
            ->select('payment_method', DB::raw('SUM(total_amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('payment_method')
            ->get()
            ->map(fn ($item) => [
                'source' => $item->payment_method ?? 'unknown',
                'total' => (float) $item->total,
                'count' => (int) $item->count,
            ])
            ->toArray();

        $unexpectedIncome = (float) UnexpectedTransaction::where('jenis', 'pemasukan')
            ->whereBetween('created_at', [$start, $end])
            ->sum('nominal');

        return array_merge($ordersByPayment, [
            [
                'source' => 'unexpected_income',
                'total' => $unexpectedIncome,
                'count' => UnexpectedTransaction::where('jenis', 'pemasukan')
                    ->whereBetween('created_at', [$start, $end])
                    ->count(),
            ],
        ]);
    }

    private function calcExpenseBreakdown(Carbon $start, Carbon $end): array
    {
        $ingredientCosts = (float) IngredientBatch::whereBetween('received_at', [$start, $end])
            ->selectRaw('SUM(quantity * cost_per_unit) as total')
            ->value('total') ?? 0;

        $ingredientCount = IngredientBatch::whereBetween('received_at', [$start, $end])->count();

        $unexpectedExpense = (float) UnexpectedTransaction::where('jenis', 'pengeluaran')
            ->whereBetween('created_at', [$start, $end])
            ->sum('nominal');

        $unexpectedCount = UnexpectedTransaction::where('jenis', 'pengeluaran')
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $expenses = Expense::whereBetween('date', [$start, $end])
            ->select('category', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('category')
            ->get()
            ->map(fn ($item) => [
                'source' => $item->category ?? 'uncategorized',
                'total' => (float) $item->total,
                'count' => (int) $item->count,
            ])
            ->toArray();

        return array_merge($expenses, [
            [
                'source' => 'ingredient_purchase',
                'total' => $ingredientCosts,
                'count' => $ingredientCount,
            ],
            [
                'source' => 'unexpected_expense',
                'total' => $unexpectedExpense,
                'count' => $unexpectedCount,
            ],
        ]);
    }

    private function calcReceivablesOutstanding(Carbon $start, Carbon $end): float
    {
        $receivables = Receivable::where('status', '!=', Receivable::STATUS_PAID)
            ->whereBetween('created_at', [$start, $end])
            ->get();

        return (float) $receivables->sum(function ($receivable) {
            return max(0, (float) $receivable->amount - (float) $receivable->paid_amount);
        });
    }

    // ─── Rigid Report ──────────────────────────────────────────────────

    /**
     * Generate Income Statement + Cash Flow Statement for a date range.
     * Cash basis: orders with payment_method != 'bayar_nanti' are recognized.
     */
    private function generateRigid(array $params): ReportData
    {
        $dateStart = $params['date_start'] ?? Carbon::now()->startOfMonth()->toDateString();
        $dateEnd = $params['date_end'] ?? Carbon::now()->toDateString();

        $s = Carbon::parse($dateStart)->startOfDay();
        $e = Carbon::parse($dateEnd)->endOfDay();

        $pendapatanOrders = (float) Order::where('payment_method', '!=', 'bayar_nanti')
            ->whereBetween('created_at', [$s, $e])
            ->sum('total_amount');

        $pendapatanUnexpected = (float) UnexpectedTransaction::where('jenis', 'pemasukan')
            ->whereBetween('created_at', [$s, $e])
            ->sum('nominal');

        $pendapatan = $pendapatanOrders + $pendapatanUnexpected;

        $hpp = (float) IngredientBatch::whereBetween('received_at', [$s, $e])
            ->selectRaw('COALESCE(SUM(quantity * cost_per_unit), 0) as total')
            ->value('total');

        $labaKotor = $pendapatan - $hpp;

        $bebanOperasional = 0;

        $bebanTakTerduga = (float) UnexpectedTransaction::where('jenis', 'pengeluaran')
            ->whereBetween('created_at', [$s, $e])
            ->sum('nominal');

        $labaRugiBersih = $labaKotor - $bebanOperasional - $bebanTakTerduga;

        $receivablePayments = (float) Receivable::whereIn('status', [
            Receivable::STATUS_PAID,
            Receivable::STATUS_PARTIAL,
        ])
            ->whereBetween('updated_at', [$s, $e])
            ->sum('paid_amount');

        $arusKasMasuk = $pendapatan + $receivablePayments;
        $arusKasKeluar = $bebanOperasional + $hpp + $bebanTakTerduga;
        $arusKasBersih = $arusKasMasuk - $arusKasKeluar;

        $data = [
            'meta' => [
                'date_start' => $dateStart,
                'date_end' => $dateEnd,
                'generated_at' => now()->toDateTimeString(),
                'type' => 'rigid',
            ],
            'income_statement' => [
                'pendapatan' => $pendapatan,
                'pendapatan_orders' => $pendapatanOrders,
                'pendapatan_unexpected' => $pendapatanUnexpected,
                'hpp' => $hpp,
                'laba_kotor' => $labaKotor,
                'beban_operasional' => $bebanOperasional,
                'beban_tak_terduga' => $bebanTakTerduga,
                'laba_rugi_bersih' => $labaRugiBersih,
            ],
            'cash_flow' => [
                'arus_kas_masuk' => $arusKasMasuk,
                'pendapatan' => $pendapatan,
                'receivable_payments' => $receivablePayments,
                'arus_kas_keluar' => $arusKasKeluar,
                'beban_operasional' => $bebanOperasional,
                'hpp' => $hpp,
                'beban_tak_terduga' => $bebanTakTerduga,
                'arus_kas_bersih' => $arusKasBersih,
                'saldo_awal' => 0,
                'saldo_akhir' => $arusKasBersih,
            ],
        ];

        return ReportData::fromRigidReport($data, $dateStart, $dateEnd);
    }

    // ─── Custom Report ─────────────────────────────────────────────────

    /**
     * Generate a custom aggregated report with category filtering.
     *
     * Category identifier prefixes:
     * - menu:{id}              Menu category (by categories.id)
     * - unexpected_income      UnexpectedTransaction jenis='pemasukan'
     * - bahan_baku             IngredientBatch purchases
     * - unexpected_expense     UnexpectedTransaction jenis='pengeluaran'
     *
     * Empty categories array = include ALL categories from all sources.
     */
    private function generateCustom(array $params): ReportData
    {
        $dateStart = $params['date_start'] ?? Carbon::now()->startOfYear()->toDateString();
        $dateEnd = $params['date_end'] ?? Carbon::now()->toDateString();
        $aggregation = $params['aggregation'] ?? 'monthly';
        $categoryFilters = $params['categories'] ?? [];

        $config = [
            'date_start' => $dateStart,
            'date_end' => $dateEnd,
            'categories' => $categoryFilters,
            'aggregation' => $aggregation,
        ];

        $from = Carbon::parse($dateStart)->startOfDay();
        $to = Carbon::parse($dateEnd)->endOfDay();

        [$menuCatIds, $includeUncInc, $includeBb, $includeUncExp]
            = $this->parseCategoryFilters($categoryFilters);

        $dateExpr = $aggregation === 'daily'
            ? 'DATE(%s)'
            : "DATE_TRUNC('month', %s)::date";

        $unionParts = [];
        $bindings = [];

        if ($menuCatIds !== null) {
            $unionParts[] = $this->incomeMenuSql($from, $to, $dateExpr, $menuCatIds, $bindings);
        }

        if ($includeUncInc) {
            $unionParts[] = $this->incomeUnexpectedSql($from, $to, $dateExpr, $bindings);
        }

        if ($includeBb) {
            $unionParts[] = $this->expenseBahanBakuSql($from, $to, $dateExpr, $bindings);
        }

        if ($includeUncExp) {
            $unionParts[] = $this->expenseUnexpectedSql($from, $to, $dateExpr, $bindings);
        }

        if (empty($unionParts)) {
            $rows = [];
        } else {
            $unionSql = implode("\nUNION ALL\n", $unionParts);
            $sql = "SELECT bucket_date, category, type, CAST(amount AS NUMERIC(15,2)) AS amount\n"
                  ."FROM ({$unionSql}) combined\n"
                  .'ORDER BY bucket_date, type DESC, category';

            $rows = DB::select($sql, $bindings);
        }

        $allRows = $this->fillMissingCombos(
            $rows,
            $from,
            $to,
            $aggregation,
            $menuCatIds,
            $includeUncInc,
            $includeBb,
            $includeUncExp
        );

        $this->sortRows($allRows);
        $runningTotal = 0;
        foreach ($allRows as &$row) {
            $sign = $row['type'] === 'Income' ? 1 : -1;
            $runningTotal += $sign * (float) $row['amount'];
            $row['running_total'] = round($runningTotal, 2);
        }
        unset($row);

        $totalIncome = 0;
        $totalExpense = 0;
        foreach ($allRows as $row) {
            if ($row['type'] === 'Income') {
                $totalIncome += (float) $row['amount'];
            } else {
                $totalExpense += (float) $row['amount'];
            }
        }

        $data = [
            'config' => $config,
            'rows' => $allRows,
            'summary' => [
                'total_income' => round($totalIncome, 2),
                'total_expense' => round($totalExpense, 2),
                'net' => round($totalIncome - $totalExpense, 2),
            ],
        ];

        return ReportData::fromCustomReport($data, $dateStart, $dateEnd);
    }

    // ─── Custom Report Helpers ─────────────────────────────────────────

    /**
     * Parse $categoryFilters into concrete category sets.
     * Returns [menuCatIds|null, bool, bool, bool].
     * null means "ALL" for that set. Empty filters = all null + all true.
     */
    private function parseCategoryFilters(array $categoryFilters): array
    {
        $includeAll = empty($categoryFilters);

        $menuCatIds = null;
        $includeUncInc = true;
        $includeBb = true;
        $includeUncExp = true;

        if ($includeAll) {
            return [$menuCatIds, $includeUncInc, $includeBb, $includeUncExp];
        }

        $menuIds = [];
        $uncInc = false;
        $bb = false;
        $uncExp = false;

        foreach ($categoryFilters as $ident) {
            if ($ident === 'unexpected_income') {
                $uncInc = true;
            } elseif ($ident === 'bahan_baku') {
                $bb = true;
            } elseif ($ident === 'unexpected_expense') {
                $uncExp = true;
            } elseif (str_starts_with($ident, 'menu:')) {
                $id = (int) substr($ident, 5);
                if ($id > 0) {
                    $menuIds[] = $id;
                }
            }
        }

        return [
            ! empty($menuIds) ? $menuIds : null,
            $uncInc,
            $bb,
            $uncExp,
        ];
    }

    /**
     * Income from paid orders, grouped by menu category.
     */
    private function incomeMenuSql(Carbon $from, Carbon $to, string $dateExpr, ?array $menuCatIds, array &$bindings): string
    {
        $dateCol = sprintf($dateExpr, 'o.created_at');

        $where = "(o.payment_method IS DISTINCT FROM 'bayar_nanti' OR o.payment_method IS NULL) AND o.created_at BETWEEN ? AND ?";
        $bindings[] = $from;
        $bindings[] = $to;

        if (! empty($menuCatIds)) {
            $placeholders = $this->buildInPlaceholders($menuCatIds);
            $where .= " AND c.id IN ({$placeholders})";
            foreach ($menuCatIds as $id) {
                $bindings[] = $id;
            }
        }

        return "SELECT {$dateCol} AS bucket_date, c.name AS category, 'Income' AS type,\n"
              ."       COALESCE(SUM(oi.subtotal), 0) AS amount\n"
              ."FROM order_items oi\n"
              ."JOIN orders o ON o.id = oi.order_id\n"
              ."JOIN menus m ON m.id = oi.menu_id\n"
              ."JOIN categories c ON c.id = m.category_id\n"
              ."WHERE {$where}\n"
              ."GROUP BY {$dateCol}, c.name";
    }

    /**
     * Income from UnexpectedTransaction where jenis = 'pemasukan'.
     */
    private function incomeUnexpectedSql(Carbon $from, Carbon $to, string $dateExpr, array &$bindings): string
    {
        $dateCol = sprintf($dateExpr, 'created_at');

        $bindings[] = $from;
        $bindings[] = $to;

        return "SELECT {$dateCol} AS bucket_date, 'Unexpected Income' AS category, 'Income' AS type,\n"
              ."       COALESCE(SUM(nominal), 0) AS amount\n"
              ."FROM unexpected_transactions\n"
              ."WHERE jenis = 'pemasukan' AND created_at BETWEEN ? AND ?\n"
              ."GROUP BY {$dateCol}";
    }

    /**
     * Expense from IngredientBatch purchases as "Bahan Baku".
     */
    private function expenseBahanBakuSql(Carbon $from, Carbon $to, string $dateExpr, array &$bindings): string
    {
        $dateCol = sprintf($dateExpr, 'ib.received_at');

        $bindings[] = $from;
        $bindings[] = $to;

        return "SELECT {$dateCol} AS bucket_date, 'Bahan Baku' AS category, 'Expense' AS type,\n"
              ."       COALESCE(SUM(ib.quantity * ib.cost_per_unit), 0) AS amount\n"
              ."FROM ingredient_batches ib\n"
              ."WHERE ib.received_at BETWEEN ? AND ?\n"
              ."GROUP BY {$dateCol}";
    }

    /**
     * Expense from UnexpectedTransaction where jenis = 'pengeluaran'.
     */
    private function expenseUnexpectedSql(Carbon $from, Carbon $to, string $dateExpr, array &$bindings): string
    {
        $dateCol = sprintf($dateExpr, 'created_at');

        $bindings[] = $from;
        $bindings[] = $to;

        return "SELECT {$dateCol} AS bucket_date, 'Unexpected' AS category, 'Expense' AS type,\n"
              ."       COALESCE(SUM(nominal), 0) AS amount\n"
              ."FROM unexpected_transactions\n"
              ."WHERE jenis = 'pengeluaran' AND created_at BETWEEN ? AND ?\n"
              ."GROUP BY {$dateCol}";
    }

    /**
     * Fill zero-amount rows for every expected (date, category, type) combination
     * so empty categories are not hidden.
     */
    private function fillMissingCombos(
        array $rows,
        Carbon $from,
        Carbon $to,
        string $aggregation,
        ?array $menuCatIds,
        bool $includeUncInc,
        bool $includeBb,
        bool $includeUncExp,
    ): array {
        $buckets = [];
        $cur = $from->copy();
        if ($aggregation === 'daily') {
            while ($cur->lte($to)) {
                $buckets[] = $cur->format('Y-m-d');
                $cur->addDay();
            }
        } else {
            $cur = $cur->startOfMonth();
            $endMonth = $to->copy()->endOfMonth()->startOfMonth();
            while ($cur->lte($endMonth)) {
                $buckets[] = $cur->format('Y-m-d');
                $cur->addMonth();
            }
        }

        $incCategoryNames = [];

        if ($menuCatIds !== null) {
            $incCategoryNames = Category::whereIn('id', $menuCatIds)
                ->pluck('name')
                ->toArray();
        } else {
            $incCategoryNames = Category::pluck('name')->toArray();
        }

        $keyed = [];
        foreach ($rows as $r) {
            $bd = $r->bucket_date;
            if ($aggregation === 'monthly' && strlen($bd) > 10) {
                $bd = substr($bd, 0, 10);
            }
            $keyed["{$bd}|{$r->category}|{$r->type}"] = $r;
        }

        $filled = [];
        foreach ($buckets as $date) {
            foreach ($incCategoryNames as $catName) {
                $k = "{$date}|{$catName}|Income";
                $filled[] = isset($keyed[$k])
                    ? $this->toArray($keyed[$k])
                    : $this->makeRow($date, $catName, 'Income', 0);
            }

            if ($includeUncInc) {
                $k = "{$date}|Unexpected Income|Income";
                $filled[] = isset($keyed[$k])
                    ? $this->toArray($keyed[$k])
                    : $this->makeRow($date, 'Unexpected Income', 'Income', 0);
            }

            if ($includeBb) {
                $k = "{$date}|Bahan Baku|Expense";
                $filled[] = isset($keyed[$k])
                    ? $this->toArray($keyed[$k])
                    : $this->makeRow($date, 'Bahan Baku', 'Expense', 0);
            }

            if ($includeUncExp) {
                $k = "{$date}|Unexpected|Expense";
                $filled[] = isset($keyed[$k])
                    ? $this->toArray($keyed[$k])
                    : $this->makeRow($date, 'Unexpected', 'Expense', 0);
            }
        }

        return $filled;
    }

    /**
     * Sort rows: date asc, then Income before Expense, then category asc.
     */
    private function sortRows(array &$rows): void
    {
        usort($rows, function (array $a, array $b) {
            $cmp = strcmp($a['date'], $b['date']);
            if ($cmp !== 0) {
                return $cmp;
            }
            $typeCmp = strcmp($b['type'], $a['type']);
            if ($typeCmp !== 0) {
                return $typeCmp;
            }

            return strcmp($a['category'], $b['category']);
        });
    }

    private function buildInPlaceholders(array $items): string
    {
        return implode(', ', array_fill(0, count($items), '?'));
    }

    private function makeRow(string $date, string $category, string $type, float $amount): array
    {
        return [
            'date' => $date,
            'category' => $category,
            'type' => $type,
            'amount' => round($amount, 2),
            'running_total' => 0,
        ];
    }

    private function toArray(\stdClass $obj): array
    {
        return [
            'date' => $obj->bucket_date,
            'category' => $obj->category,
            'type' => $obj->type,
            'amount' => (float) $obj->amount,
            'running_total' => 0,
        ];
    }
}
