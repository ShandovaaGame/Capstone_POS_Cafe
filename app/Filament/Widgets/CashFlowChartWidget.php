<?php

namespace App\Filament\Widgets;

use App\Models\IngredientBatch;
use App\Models\Order;
use App\Models\UnexpectedTransaction;
use Carbon\Carbon;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Livewire\Attributes\On;

class CashFlowChartWidget extends ChartWidget
{
    public string $period = 'month';

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 2;

    public static function isLazy(): bool { return false; }

    public function getHeading(): string { return 'Analisis Arus Kas'; }

    public function getDescription(): ?string { return 'Pemasukan dari transaksi vs Pengeluaran'; }

    #[On('cashflow-period-changed')]
    public function onPeriodChanged(string $period): void
    {
        $this->period = $period;
    }

    private function incomeQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return Order::where('status', Order::STATUS_SELESAI);
    }

    private function slotIncome($start, $end): float
    {
        $orders = (float) $this->incomeQuery()
            ->whereBetween('created_at', [$start, $end])->sum('total_amount');
        $unexpected = (float) UnexpectedTransaction::where('jenis', 'pemasukan')
            ->whereBetween('created_at', [$start, $end])->sum('nominal');
        return $orders + $unexpected;
    }

    private function slotExpense($start, $end): float
    {
        $batches = (float) IngredientBatch::whereBetween('received_at', [$start, $end])
            ->selectRaw('SUM(quantity * cost_per_unit) as total')->value('total') ?? 0;
        $unexpected = (float) UnexpectedTransaction::where('jenis', 'pengeluaran')
            ->whereBetween('created_at', [$start, $end])->sum('nominal');
        return $batches + $unexpected;
    }

    private function buildData(): array
    {
        return match ($this->period) {
            'day'      => $this->byHour(),
            'year'     => $this->byMonthOfYear(),
            'all_time' => $this->byMonth(),
            default    => $this->byEvenDay(),   // month
        };
    }

    /** Hari Ini: per 3 jam (00:00, 03:00, 06:00, …, 21:00) */
    private function byHour(): array
    {
        $labels = $incData = $expData = [];
        $today  = Carbon::today();

        foreach ([0, 3, 6, 9, 12, 15, 18, 21] as $h) {
            $start = $today->copy()->setHour($h)->setMinute(0)->setSecond(0);
            $end   = $today->copy()->setHour($h + 2)->setMinute(59)->setSecond(59);

            $labels[]  = sprintf('%02d:00', $h);
            $incData[] = $this->slotIncome($start, $end);
            $expData[] = $this->slotExpense($start, $end);
        }
        return compact('labels', 'incData', 'expData');
    }

    /** Bulan Ini: tanggal genap saja (2, 4, 6 … akhir bulan) */
    private function byEvenDay(): array
    {
        $labels = $incData = $expData = [];
        $s      = Carbon::now()->startOfMonth();
        $e      = Carbon::now()->endOfMonth();
        $cur    = $s->copy();

        while ($cur->lte($e)) {
            if ($cur->day % 2 === 0) {
                $dayStart  = $cur->copy()->startOfDay();
                $dayEnd    = $cur->copy()->endOfDay();
                $labels[]  = $cur->format('d/m');
                $incData[] = $this->slotIncome($dayStart, $dayEnd);
                $expData[] = $this->slotExpense($dayStart, $dayEnd);
            }
            $cur->addDay();
        }
        return compact('labels', 'incData', 'expData');
    }

    /** Tahun Ini: Januari–Desember */
    private function byMonthOfYear(): array
    {
        $monthNames = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        $year       = Carbon::now()->year;
        $labels = $incData = $expData = [];

        for ($m = 1; $m <= 12; $m++) {
            $mStart    = Carbon::create($year, $m, 1)->startOfMonth();
            $mEnd      = Carbon::create($year, $m, 1)->endOfMonth();
            $labels[]  = $monthNames[$m - 1];
            $incData[] = $this->slotIncome($mStart, $mEnd);
            $expData[] = $this->slotExpense($mStart, $mEnd);
        }
        return compact('labels', 'incData', 'expData');
    }

    /** Semua Waktu: per 6 bulan mulai Jan 2026 */
    private function byMonth(): array
    {
        $e      = Carbon::now()->endOfDay();
        $labels = $incData = $expData = [];
        $cur    = Carbon::createFromDate(2026, 1, 1)->startOfMonth();

        while ($cur->lte($e)) {
            $periodEnd = $cur->copy()->addMonths(5)->endOfMonth();
            $slotEnd   = $periodEnd->gt($e) ? $e->copy() : $periodEnd;

            $labels[]  = $cur->format('M Y');
            $incData[] = $this->slotIncome($cur->copy()->startOfDay(), $slotEnd);
            $expData[] = $this->slotExpense($cur->copy()->startOfDay(), $slotEnd);

            $cur->addMonths(6);
        }
        return compact('labels', 'incData', 'expData');
    }

    protected function getData(): array
    {
        ['labels' => $labels, 'incData' => $incData, 'expData' => $expData] = $this->buildData();

        $shared = [
            'fill'            => true,
            'tension'         => 0.45,
            'borderWidth'     => 2.5,
            'pointRadius'     => 4,
            'pointHoverRadius'=> 7,
            'pointBorderWidth'=> 2,
            'pointBorderColor'=> '#ffffff',
            'cubicInterpolationMode' => 'monotone',
        ];

        return [
            'datasets' => [
                array_merge($shared, [
                    'label'                => 'Pemasukan',
                    'data'                 => $incData,
                    'borderColor'          => '#22c55e',
                    'backgroundColor'      => 'rgba(34,197,94,0.12)',
                    'pointBackgroundColor' => '#22c55e',
                ]),
                array_merge($shared, [
                    'label'                => 'Pengeluaran',
                    'data'                 => $expData,
                    'borderColor'          => '#ef4444',
                    'backgroundColor'      => 'rgba(239,68,68,0.12)',
                    'pointBackgroundColor' => '#ef4444',
                ]),
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string { return 'line'; }

    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<'JS'
        {
            animation: { duration: 600 },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { usePointStyle: true, padding: 24, color: '#94a3b8' }
                },
                tooltip: {
                    backgroundColor: 'rgba(15,23,42,0.92)',
                    titleColor: '#e2e8f0',
                    bodyColor: '#94a3b8',
                    borderColor: 'rgba(255,255,255,0.08)',
                    borderWidth: 1,
                    padding: 12,
                    callbacks: {
                        label: (ctx) => {
                            const v = ctx.parsed.y;
                            const fmt = new Intl.NumberFormat('id-ID').format(Math.round(v));
                            return ' ' + ctx.dataset.label + ': Rp ' + fmt + ',-';
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { color: 'rgba(148,163,184,0.07)' },
                    ticks: { color: '#64748b', maxRotation: 0 },
                    border: { display: false }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(148,163,184,0.07)' },
                    ticks: {
                        color: '#64748b',
                        padding: 8,
                        callback: (v) => {
                            if (v >= 1000000) return 'Rp ' + (v/1000000).toFixed(1) + 'jt';
                            if (v >= 1000)    return 'Rp ' + (v/1000).toFixed(0) + 'rb';
                            return v === 0 ? '0' : 'Rp ' + v;
                        }
                    },
                    border: { display: false }
                }
            }
        }
        JS);
    }
}
