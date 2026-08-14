<?php

namespace App\Filament\Widgets;

use App\Models\IngredientBatch;
use App\Models\Order;
use App\Models\UnexpectedTransaction;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\On;

class CashFlowStatsWidget extends StatsOverviewWidget
{
    public string $period = 'day';

    protected ?string $pollingInterval = null;

    public static function isLazy(): bool { return false; }

    protected function getColumns(): int { return 4; }

    #[On('cashflow-period-changed')]
    public function onPeriodChanged(string $period): void
    {
        $this->period = $period;
    }

    private function dateRange(): array
    {
        return match ($this->period) {
            'day'      => [Carbon::today(),              Carbon::today()->endOfDay()],
            'year'     => [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()],
            'all_time' => [Carbon::createFromDate(2000, 1, 1), Carbon::now()->endOfDay()],
            default    => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
        };
    }

    private function prevRange(): array
    {
        [$s, $e] = $this->dateRange();
        if ($this->period === 'all_time') return [$s, $e];
        $days    = $s->diffInDays($e) + 1;
        $prevEnd = $s->copy()->subDay();
        return [$prevEnd->copy()->subDays($days - 1), $prevEnd];
    }

    private function pct(float $curr, float $prev): ?float
    {
        return $prev > 0 ? (($curr - $prev) / $prev) * 100 : null;
    }

    private function totalIncome($s, $e): float
    {
        $fromOrders = (float) Order::where('status', Order::STATUS_SELESAI)
            ->whereBetween('created_at', [$s, $e])->sum('total_amount');
        $fromUnexpected = (float) UnexpectedTransaction::where('jenis', 'pemasukan')
            ->whereBetween('created_at', [$s, $e])->sum('nominal');
        return $fromOrders + $fromUnexpected;
    }

    private function totalExpense($s, $e): float
    {
        $fromBatches = (float) IngredientBatch::whereBetween('received_at', [$s, $e])
            ->selectRaw('SUM(quantity * cost_per_unit) as total')->value('total') ?? 0;
        $fromUnexpected = (float) UnexpectedTransaction::where('jenis', 'pengeluaran')
            ->whereBetween('created_at', [$s, $e])->sum('nominal');
        return $fromBatches + $fromUnexpected;
    }

    private function sparklineIncome(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $day   = now()->subDays($i)->toDateString();
            $s     = Carbon::parse($day)->startOfDay();
            $e     = Carbon::parse($day)->endOfDay();
            $data[] = $this->totalIncome($s, $e);
        }
        return $data;
    }

    private function sparklineExpense(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $day   = now()->subDays($i)->toDateString();
            $s     = Carbon::parse($day)->startOfDay();
            $e     = Carbon::parse($day)->endOfDay();
            $data[] = $this->totalExpense($s, $e);
        }
        return $data;
    }

    private function fmtK(float $n): string
    {
        return 'Rp ' . number_format($n, 0, ',', '.') . ',-';
    }

    protected function getStats(): array
    {
        [$s, $e]   = $this->dateRange();
        [$ps, $pe] = $this->prevRange();

        $income  = $this->totalIncome($s, $e);
        $expense = $this->totalExpense($s, $e);
        $net     = $income - $expense;
        $margin  = $income > 0 ? ($net / $income) * 100 : 0;

        $txnCnt = Order::where('status', Order::STATUS_SELESAI)->whereBetween('created_at', [$s, $e])->count()
                + IngredientBatch::whereBetween('received_at', [$s, $e])->count()
                + UnexpectedTransaction::whereBetween('created_at', [$s, $e])->count();

        $pIncome  = $this->totalIncome($ps, $pe);
        $pExpense = $this->totalExpense($ps, $pe);
        $pNet     = $pIncome - $pExpense;

        $icPct = $this->pct($income, $pIncome);
        $ecPct = $this->pct($expense, $pExpense);
        $ncPct = $pNet != 0 ? (($net - $pNet) / abs($pNet)) * 100 : null;

        return [
            Stat::make('Total Pemasukan', $this->fmtK($income))
                ->description($icPct !== null
                    ? ($icPct >= 0 ? '↑ ' : '↓ ') . number_format(abs($icPct), 1) . '% dari periode lalu'
                    : 'Periode pertama')
                ->descriptionIcon($icPct === null || $icPct >= 0
                    ? 'heroicon-m-arrow-trending-up'
                    : 'heroicon-m-arrow-trending-down')
                ->color($icPct === null || $icPct >= 0 ? 'success' : 'danger')
                ->chart($this->sparklineIncome()),

            Stat::make('Total Pengeluaran', $this->fmtK($expense))
                ->description($ecPct !== null
                    ? ($ecPct >= 0 ? '↑ ' : '↓ ') . number_format(abs($ecPct), 1) . '% dari periode lalu'
                    : 'Periode pertama')
                ->descriptionIcon($ecPct === null || $ecPct <= 0
                    ? 'heroicon-m-arrow-trending-down'
                    : 'heroicon-m-arrow-trending-up')
                ->color($ecPct === null || $ecPct <= 0 ? 'success' : 'danger')
                ->chart($this->sparklineExpense()),

            Stat::make('Net Cash Flow', ($net >= 0 ? '+' : '−') . $this->fmtK(abs($net)))
                ->description($ncPct !== null
                    ? ($ncPct >= 0 ? '↑ ' : '↓ ') . number_format(abs($ncPct), 1) . '% dari periode lalu'
                    : 'Periode pertama')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($net >= 0 ? 'success' : 'danger'),

            Stat::make('Net Margin', number_format($margin, 1) . '%')
                ->description($txnCnt . ' total transaksi')
                ->descriptionIcon('heroicon-m-chart-pie')
                ->color($margin >= 30 ? 'success' : ($margin >= 10 ? 'warning' : 'danger')),
        ];
    }
}
