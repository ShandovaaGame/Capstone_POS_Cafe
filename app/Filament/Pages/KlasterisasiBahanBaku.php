<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class KlasterisasiBahanBaku extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-beaker';

    protected static string|\UnitEnum|null $navigationGroup = 'Analitik';

    protected static ?string $navigationLabel = 'Klasterisasi Bahan Baku';

    protected static ?string $title = 'Klasterisasi Bahan Baku';

    protected static ?int $navigationSort = 13;

    // ── Input rentang tanggal ──────────────────────────────────────────
    public ?string $inputDateFrom  = null;
    public ?string $inputDateTo    = null;
    public ?string $dateRangeError = null;

    // ── State ──────────────────────────────────────────────────────────
    public bool    $hasResult = false;
    public ?string $lastRunAt = null;
    public ?string $errorMsg  = null;

    // ── Hasil clustering ───────────────────────────────────────────────
    public int    $bestK              = 0;
    public float  $silhouetteScore    = 0.0;
    public int    $totalIngredients   = 0;
    public string $dateFrom           = '';
    public string $dateTo             = '';
    public array  $clusters           = [];
    public array  $tableRows          = [];
    public array  $rataRataTable      = [];
    public array  $preprocessLogs     = [];

    // ── Grafik (base64 PNG) ────────────────────────────────────────────
    public ?string $chartRataKlaster = null;
    public ?string $chartBar         = null;
    public ?string $chartElbow       = null;
    public ?string $chartSilhouette  = null;

    public function getView(): string
    {
        return 'filament.pages.klasterisasi-bahan-baku';
    }

    public function getTitle(): string
    {
        return 'Klasterisasi Bahan Baku';
    }

    // ── Validasi rentang tanggal (minimal 3 bulan) ─────────────────────
    public function isDateRangeValid(): bool
    {
        if (! $this->inputDateFrom || ! $this->inputDateTo) {
            return false;
        }
        try {
            $from = Carbon::parse($this->inputDateFrom);
            $to   = Carbon::parse($this->inputDateTo);
            return $from->lte($to) && $from->diffInMonths($to) >= 3;
        } catch (\Exception) {
            return false;
        }
    }

    // ── Watcher: perbarui pesan error saat tanggal berubah ────────────
    public function updatedInputDateFrom(): void { $this->validateDateRange(); }
    public function updatedInputDateTo(): void   { $this->validateDateRange(); }

    private function validateDateRange(): void
    {
        $this->dateRangeError = null;
        if (! $this->inputDateFrom || ! $this->inputDateTo) return;

        try {
            $from   = Carbon::parse($this->inputDateFrom);
            $to     = Carbon::parse($this->inputDateTo);
            $months = $from->diffInMonths($to);

            if ($from->gt($to)) {
                $this->dateRangeError = 'Tanggal mulai tidak boleh lebih besar dari tanggal selesai.';
            } elseif ($months < 3) {
                $kurang = 3 - $months;
                $this->dateRangeError = "Rentang tanggal terlalu pendek ({$months} bulan). Minimal 3 bulan (tambah sekitar {$kurang} bulan lagi).";
            }
        } catch (\Exception) {
            $this->dateRangeError = 'Format tanggal tidak valid.';
        }
    }

    // ── Panggil FastAPI endpoint clustering bahan baku ─────────────────
    public function runClustering(): void
    {
        $this->errorMsg = null;

        if (! $this->isDateRangeValid()) {
            Notification::make()
                ->title('Rentang tanggal tidak valid')
                ->body('Pilih rentang tanggal data penggunaan bahan baku minimal 3 bulan.')
                ->warning()
                ->send();
            return;
        }

        try {
            $response = Http::timeout(180)->asJson()->post(
                'http://127.0.0.1:8001/clustering-bahan-baku',
                [
                    'date_from' => $this->inputDateFrom,
                    'date_to'   => $this->inputDateTo,
                ]
            );

            if (! $response->successful()) {
                throw new \Exception('FastAPI merespons dengan status ' . $response->status());
            }

            $data = $response->json();

            if (($data['status'] ?? '') === 'error') {
                throw new \Exception($data['message'] ?? 'Unknown error dari FastAPI');
            }

            $this->bestK            = $data['best_k']              ?? 0;
            $this->silhouetteScore  = $data['silhouette_score']    ?? 0.0;
            $this->totalIngredients = $data['total_ingredients']   ?? 0;
            $this->dateFrom         = $data['date_range']['from']  ?? '';
            $this->dateTo           = $data['date_range']['to']    ?? '';
            $this->clusters         = $data['clusters']            ?? [];
            $this->tableRows        = $data['table_rows']          ?? [];
            $this->rataRataTable    = $data['rata_rata_table']      ?? [];
            $this->preprocessLogs   = $data['preprocessing_logs']  ?? [];
            $this->chartRataKlaster = $data['charts']['rata_klaster'] ?? null;
            $this->chartBar         = $data['charts']['bar']          ?? null;
            $this->chartElbow       = $data['charts']['elbow']        ?? null;
            $this->chartSilhouette  = $data['charts']['silhouette']   ?? null;

            $this->hasResult = true;
            $this->lastRunAt = now()->locale('id')->translatedFormat('d M Y, H:i');

            // ── Simpan ke history cache (maks 3, unik per rentang tanggal) ──
            $inputFrom = $this->inputDateFrom;
            $inputTo   = $this->inputDateTo;

            $history = Cache::get('klasterisasi_bahan_baku_results_history', []);

            // Hapus entry lama dengan rentang tanggal yang sama
            $history = array_values(array_filter(
                $history,
                fn ($h) => !(
                    ($h['input_date_from'] ?? '') === $inputFrom &&
                    ($h['input_date_to']   ?? '') === $inputTo
                )
            ));

            array_unshift($history, [
                'run_at'            => $this->lastRunAt,
                'input_date_from'   => $inputFrom,
                'input_date_to'     => $inputTo,
                'best_k'            => $this->bestK,
                'silhouette_score'  => $this->silhouetteScore,
                'total_ingredients' => $this->totalIngredients,
                'date_from'         => $this->dateFrom,
                'date_to'           => $this->dateTo,
                'clusters'          => $this->clusters,
                'table_rows'        => $this->tableRows,
                'rata_rata_table'   => $this->rataRataTable,
                'preprocessing_logs'=> $this->preprocessLogs,
                'charts'            => [
                    'rata_klaster' => $this->chartRataKlaster,
                    'bar'          => $this->chartBar,
                    'elbow'        => $this->chartElbow,
                    'silhouette'   => $this->chartSilhouette,
                ],
            ]);

            $history = array_slice($history, 0, 3);
            Cache::put('klasterisasi_bahan_baku_results_history', $history, now()->addDays(30));

            Notification::make()
                ->title('Clustering Bahan Baku selesai!')
                ->body("K-Means berhasil. K optimal = {$this->bestK}, Silhouette = {$this->silhouetteScore}")
                ->success()
                ->send();

        } catch (\Exception $e) {
            $this->errorMsg  = $e->getMessage();
            $this->hasResult = false;

            Notification::make()
                ->title('Clustering Bahan Baku gagal')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('run_clustering_bahan_baku')
                ->label('Jalankan Clustering Bahan Baku')
                ->icon('heroicon-o-cpu-chip')
                ->color('primary')
                ->disabled(fn () => ! $this->isDateRangeValid())
                ->requiresConfirmation()
                ->modalHeading('Jalankan Clustering Bahan Baku')
                ->modalDescription('Proses ini akan membaca data pemakaian bahan baku harian sesuai rentang tanggal yang dipilih, lalu mengklasterisasi tiap bahan baku berdasarkan total penggunaannya menggunakan K-Means. Pastikan FastAPI sudah berjalan. Lanjutkan?')
                ->modalSubmitActionLabel('Ya, Jalankan')
                ->action(fn () => $this->runClustering()),
        ];
    }
}
