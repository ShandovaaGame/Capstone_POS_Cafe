<?php

namespace App\Filament\Pages;

use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class KlasterisasiMenu extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static string|\UnitEnum|null $navigationGroup = 'Analitik';

    protected static ?string $navigationLabel = 'Klasterisasi Menu Penjualan';

    protected static ?string $title = 'Klasterisasi Menu Penjualan';

    protected static ?int $navigationSort = 11;

    // ── Input pengguna ─────────────────────────────────────────────────────
    public string $inputDateFrom = '';
    public string $inputDateTo   = '';

    // ── State ──────────────────────────────────────────────────────────────
    public bool    $isRunning = false;
    public bool    $hasResult = false;
    public ?string $lastRunAt = null;
    public ?string $errorMsg  = null;

    // ── Tanggal input yang digunakan saat run terakhir ─────────────────────
    public string $usedDateFrom = '';
    public string $usedDateTo   = '';

    // ── Hasil clustering ───────────────────────────────────────────────────
    public int    $bestK           = 0;
    public float  $silhouetteScore = 0.0;
    public int    $totalMenu       = 0;
    public string $dateFrom        = '';   // actual min date in returned data
    public string $dateTo          = '';   // actual max date in returned data

    public array  $preprocessLogs  = [];
    public array  $tableRows       = [];   // LAPORAN HASIL CLUSTERING
    public array  $kategoriRows    = [];   // LAPORAN KATEGORISASI
    public array  $clusterSummary  = [];   // RATA-RATA per klaster

    // ── Grafik (base64 PNG) ────────────────────────────────────────────────
    public ?string $chartBarJumlah     = null;
    public ?string $chartBarKeuntungan = null;
    public ?string $chartKategorisasi  = null;
    public ?string $chartElbow         = null;
    public ?string $chartSilhouette    = null;

    public function getView(): string
    {
        return 'filament.pages.klasterisasi-menu';
    }

    public function getTitle(): string
    {
        return 'Klasterisasi Menu Penjualan';
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

    // ── Panggil FastAPI dan simpan hasil ───────────────────────────────────
    public function runClustering(): void
    {
        // Validasi tanggal sebelum memanggil FastAPI
        if (! $this->isDatesValid()) {
            Notification::make()
                ->title('Rentang tanggal belum valid')
                ->body('Isi "Dari Tanggal" dan "Sampai Tanggal" dengan rentang minimal 3 bulan.')
                ->warning()
                ->send();
            return;
        }

        $this->isRunning = true;
        $this->errorMsg  = null;

        try {
            $response = Http::timeout(180)->post('http://127.0.0.1:8001/clustering', [
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

            // Simpan ke state Livewire
            $this->bestK           = $data['best_k']           ?? 0;
            $this->silhouetteScore = $data['silhouette_score'] ?? 0.0;
            $this->totalMenu       = $data['total_menu']       ?? 0;
            $this->dateFrom        = $data['date_range']['from'] ?? $this->inputDateFrom;
            $this->dateTo          = $data['date_range']['to']   ?? $this->inputDateTo;
            $this->preprocessLogs  = $data['preprocessing_logs'] ?? [];
            $this->tableRows       = $data['table_rows']          ?? [];
            $this->kategoriRows    = $data['kategorisasi_rows']   ?? [];
            $this->clusterSummary  = $data['cluster_summary']     ?? [];
            $this->chartBarJumlah     = $data['charts']['bar_jumlah']     ?? null;
            $this->chartBarKeuntungan = $data['charts']['bar_keuntungan'] ?? null;
            $this->chartKategorisasi  = $data['charts']['kategorisasi']   ?? null;
            $this->chartElbow         = $data['charts']['elbow']           ?? null;
            $this->chartSilhouette    = $data['charts']['silhouette']      ?? null;

            $this->hasResult    = true;
            $this->lastRunAt    = now()->locale('id')->translatedFormat('d M Y, H:i');
            $this->usedDateFrom = $this->inputDateFrom;
            $this->usedDateTo   = $this->inputDateTo;

            // ── Simpan ke cache (list 3 hasil terbaru, unik per rentang tanggal) ──
            $newResult = array_merge($data, [
                'last_run_at'      => $this->lastRunAt,
                'input_date_from'  => $this->inputDateFrom,
                'input_date_to'    => $this->inputDateTo,
            ]);

            $results = Cache::get('klasterisasi_menu_results', []);

            // Jika date range sama → ganti (replace) hasil yang sudah ada
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
                $results = array_slice($results, 0, 3);
            }

            Cache::put('klasterisasi_menu_results', $results, now()->addDays(30));

            Notification::make()
                ->title('Clustering selesai!')
                ->body("K optimal = {$this->bestK} | Silhouette = {$this->silhouetteScore}")
                ->success()
                ->send();

        } catch (\Throwable $e) {
            $this->errorMsg  = $e->getMessage();
            $this->hasResult = false;

            Notification::make()
                ->title('Clustering gagal')
                ->body($e->getMessage())
                ->danger()
                ->send();
        } finally {
            $this->isRunning = false;
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('run_clustering')
                ->label('Jalankan Clustering')
                ->icon('heroicon-o-cpu-chip')
                ->color('primary')
                ->action(fn () => $this->runClustering()),
        ];
    }
}
