<?php

namespace App\Filament\Pages;

use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class AsosiatifMenu extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-link';

    protected static string|\UnitEnum|null $navigationGroup = 'Analitik';

    protected static ?string $navigationLabel = 'Asosiatif Menu';

    protected static ?string $title = 'Asosiatif Menu';

    protected static ?int $navigationSort = 12;

    // ── Input pengguna ─────────────────────────────────────────────────────
    public string $inputDateFrom = '';
    public string $inputDateTo   = '';

    // ── State ──────────────────────────────────────────────────────────────
    public bool    $hasResult = false;
    public ?string $lastRunAt = null;
    public ?string $errorMsg  = null;

    // ── Tanggal yang digunakan saat run (berbeda dari input aktif) ──────────
    public string $usedDateFrom = '';
    public string $usedDateTo   = '';

    // ── Hasil association rule ─────────────────────────────────────────────
    public int    $totalRules        = 0;
    public int    $totalTransactions = 0;
    public float  $minSupport        = 0.0;
    public float  $minConfidence     = 0.0;
    public string $dateFrom          = '';
    public string $dateTo            = '';
    public array  $rules             = [];
    public array  $freq1Itemsets     = [];
    public array  $freq2Itemsets     = [];
    public array  $preprocessLogs    = [];

    // ── Grafik ─────────────────────────────────────────────────────────────
    public ?string $chartSupConf  = null;
    public ?string $chartTopRules = null;
    public ?string $chartFreqItem = null;

    public function getView(): string
    {
        return 'filament.pages.asosiatif-menu';
    }

    public function getTitle(): string
    {
        return 'Asosiatif Menu';
    }

    // ── Validasi rentang tanggal (minimal 3 bulan) ─────────────────────────
    public function isDatesValid(): bool
    {
        if (empty($this->inputDateFrom) || empty($this->inputDateTo)) {
            return false;
        }
        try {
            $from = Carbon::parse($this->inputDateFrom);
            $to   = Carbon::parse($this->inputDateTo);
            return $to->gt($from) && $from->copy()->addMonths(3)->lte($to);
        } catch (\Throwable) {
            return false;
        }
    }

    // ── Panggil FastAPI endpoint association rule ──────────────────────────
    public function runAssociation(): void
    {
        if (! $this->isDatesValid()) {
            Notification::make()
                ->title('Rentang tanggal belum valid')
                ->body('Isi "Dari Tanggal" dan "Sampai Tanggal" dengan rentang minimal 3 bulan.')
                ->warning()
                ->send();
            return;
        }

        $this->errorMsg = null;

        try {
            $response = Http::timeout(120)->post('http://127.0.0.1:8001/association', [
                'date_from' => $this->inputDateFrom,
                'date_to'   => $this->inputDateTo,
            ]);

            if (! $response->successful()) {
                throw new \Exception('FastAPI merespons dengan status ' . $response->status());
            }

            $data = $response->json();

            if (($data['status'] ?? '') === 'error') {
                throw new \Exception($data['message'] ?? 'Unknown error dari FastAPI');
            }

            // Simpan state untuk tampilan
            $this->totalRules        = $data['total_rules']        ?? 0;
            $this->totalTransactions = $data['total_transactions'] ?? 0;
            $this->minSupport        = $data['min_support']        ?? 0.0;
            $this->minConfidence     = $data['min_confidence']     ?? 0.0;
            // date_range = actual min/max date in the returned data (post-filter)
            // filter_date_from/to = what we sent as filter (echoed back by FastAPI)
            $this->dateFrom          = $data['date_range']['from']   ?? $this->inputDateFrom;
            $this->dateTo            = $data['date_range']['to']     ?? $this->inputDateTo;
            $this->rules             = $data['rules']              ?? [];
            $this->freq1Itemsets     = $data['freq_1_itemsets']    ?? [];
            $this->freq2Itemsets     = $data['freq_2_itemsets']    ?? [];
            $this->preprocessLogs    = $data['preprocessing_logs'] ?? [];
            $this->chartSupConf      = $data['charts']['sup_conf']  ?? null;
            $this->chartTopRules     = $data['charts']['top_rules'] ?? null;
            $this->chartFreqItem     = $data['charts']['freq_item'] ?? null;

            $this->hasResult    = true;
            $this->lastRunAt    = now()->locale('id')->translatedFormat('d M Y, H:i');
            $this->usedDateFrom = $this->inputDateFrom;
            $this->usedDateTo   = $this->inputDateTo;

            // ── Simpan ke cache (list 3 hasil terbaru, unik per date range) ──
            $newResult = array_merge($data, [
                'last_run_at'      => $this->lastRunAt,
                'input_date_from'  => $this->inputDateFrom,
                'input_date_to'    => $this->inputDateTo,
            ]);

            $results = Cache::get('asosiatif_menu_results', []);

            // Jika date range sama → ganti (update) hasil yang sudah ada
            $replaced = false;
            foreach ($results as $idx => $r) {
                if (($r['input_date_from'] ?? '') === $this->inputDateFrom
                    && ($r['input_date_to']   ?? '') === $this->inputDateTo) {
                    array_splice($results, $idx, 1);
                    array_unshift($results, $newResult);
                    $replaced = true;
                    break;
                }
            }

            if (! $replaced) {
                array_unshift($results, $newResult);
            }

            Cache::put('asosiatif_menu_results', $results, now()->addDays(30));

            Notification::make()
                ->title('Association Rule selesai!')
                ->body("Ditemukan {$this->totalRules} rules dari {$this->totalTransactions} transaksi.")
                ->success()
                ->send();

        } catch (\Exception $e) {
            $this->errorMsg  = $e->getMessage();
            $this->hasResult = false;

            Notification::make()
                ->title('Gagal menjalankan Association Rule')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('run_association')
                ->label('Jalankan Association Rule')
                ->icon('heroicon-o-play')
                ->color('primary')
                ->action(fn () => $this->runAssociation()),
        ];
    }
}
