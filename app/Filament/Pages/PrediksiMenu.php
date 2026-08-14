<?php

namespace App\Filament\Pages;

use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class PrediksiMenu extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static string|\UnitEnum|null $navigationGroup = 'Analitik';

    protected static ?string $navigationLabel = 'Prediksi Menu';

    protected static ?string $title = 'Prediksi Menu';

    protected static ?int $navigationSort = 13;

    // ── Input rentang tanggal dari admin ───────────────────────────────
    public string $inputDateFrom  = '';
    public string $inputDateTo    = '';
    public string $dateRangeError = '';

    // ── State ──────────────────────────────────────────────────────────
    public bool    $hasResult = false;
    public ?string $lastRunAt = null;
    public ?string $errorMsg  = null;

    // ── Hasil prediksi ─────────────────────────────────────────────────
    public int    $totalMenu          = 0;
    public int    $forecastDays       = 0;
    public string $dateFrom           = '';
    public string $dateTo             = '';
    public string $dateForecastFrom   = '';
    public string $dateForecastTo     = '';
    public array  $predictions        = [];
    public array  $summaryTable       = [];
    public array  $preprocessLogs     = [];

    // ── Grafik ─────────────────────────────────────────────────────────
    public ?string $chartForecastAll       = null;
    public ?string $chartFeatureImportance = null;
    public ?string $chartAllItems          = null;
    public array   $chartPerMenu           = [];

    public function getView(): string
    {
        return 'filament.pages.prediksi-menu';
    }

    public function getTitle(): string
    {
        return 'Prediksi Menu';
    }

    // ── Validasi rentang tanggal (min 3 bulan) ─────────────────────────
    public function isDateRangeValid(): bool
    {
        if (! $this->inputDateFrom || ! $this->inputDateTo) {
            return false;
        }
        try {
            $from = Carbon::parse($this->inputDateFrom);
            $to   = Carbon::parse($this->inputDateTo);
            return $to->greaterThan($from) && $from->diffInMonths($to) >= 3;
        } catch (\Throwable) {
            return false;
        }
    }

    // ── Panggil FastAPI endpoint prediksi ──────────────────────────────
    public function runPrediction(): void
    {
        $this->errorMsg       = null;
        $this->dateRangeError = '';

        if (! $this->inputDateFrom || ! $this->inputDateTo) {
            $this->dateRangeError = 'Harap isi rentang tanggal data penjualan terlebih dahulu.';
            return;
        }

        $from = Carbon::parse($this->inputDateFrom);
        $to   = Carbon::parse($this->inputDateTo);

        if ($to->lessThanOrEqualTo($from)) {
            $this->dateRangeError = 'Tanggal akhir harus lebih besar dari tanggal awal.';
            return;
        }

        if ($from->diffInMonths($to) < 3) {
            $this->dateRangeError = 'Rentang tanggal data penjualan minimal 3 bulan.';
            return;
        }

        try {
            $response = Http::timeout(600)->asJson()->post('http://127.0.0.1:8001/prediction', [
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

            $this->totalMenu        = $data['total_menu']             ?? 0;
            $this->forecastDays     = $data['forecast_days']          ?? 0;
            $this->dateFrom         = $data['date_range']['from']     ?? '';
            $this->dateTo           = $data['date_range']['to']       ?? '';
            $this->dateForecastFrom = $data['forecast_range']['from'] ?? '';
            $this->dateForecastTo   = $data['forecast_range']['to']   ?? '';
            $this->predictions      = $data['predictions']            ?? [];
            $this->summaryTable     = $data['summary_table']          ?? [];
            $this->preprocessLogs   = $data['preprocessing_logs']    ?? [];

            $charts = $data['charts'] ?? [];
            $this->chartForecastAll       = $charts['forecast_all']       ?? null;
            $this->chartFeatureImportance = $charts['feature_importance'] ?? null;
            $this->chartAllItems          = $charts['all_items']          ?? null;
            $this->chartPerMenu           = $charts['per_menu']           ?? [];

            $this->hasResult = true;
            $this->lastRunAt = now()->locale('id')->translatedFormat('d M Y, H:i');

            // ── Simpan ke history (max 3, unik per date range) ──────────────
            $history = Cache::get('prediksi_menu_results_history', []);

            // Hapus entry dengan date range yang sama (replace)
            $inputFrom = $this->inputDateFrom;
            $inputTo   = $this->inputDateTo;
            $history   = array_values(array_filter(
                $history,
                fn($h) => !(($h['input_date_from'] ?? '') === $inputFrom
                          && ($h['input_date_to']   ?? '') === $inputTo)
            ));

            // Tambahkan entry baru di awal (terbaru pertama)
            array_unshift($history, [
                'run_at'               => $this->lastRunAt,
                'input_date_from'      => $this->inputDateFrom,
                'input_date_to'        => $this->inputDateTo,
                'date_from'            => $this->dateFrom,
                'date_to'              => $this->dateTo,
                'date_forecast_from'   => $this->dateForecastFrom,
                'date_forecast_to'     => $this->dateForecastTo,
                'total_menu'           => $this->totalMenu,
                'forecast_days'        => $this->forecastDays,
                'predictions'          => $this->predictions,
                'summary_table'        => $this->summaryTable,
                'chart_feature_importance' => $this->chartFeatureImportance,
            ]);

            Cache::put('prediksi_menu_results_history', $history, now()->addDays(30));

            // Tetap simpan key lama agar backward-compatible
            Cache::put(
                'prediksi_menu_last_result',
                array_merge($data, ['last_run_at' => $this->lastRunAt]),
                now()->addDays(7)
            );

            Notification::make()
                ->title('Prediksi selesai!')
                ->body("Berhasil memprediksi {$this->totalMenu} menu untuk {$this->forecastDays} hari ke depan.")
                ->success()
                ->send();

        } catch (\Exception $e) {
            $this->errorMsg  = $e->getMessage();
            $this->hasResult = false;

            Notification::make()
                ->title('Prediksi gagal')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('run_prediction')
                ->label('Jalankan Prediksi')
                ->icon('heroicon-o-sparkles')
                ->color('primary')
                ->disabled(fn() => ! $this->isDateRangeValid())
                ->action($this->runPrediction(...)),
        ];
    }
}
