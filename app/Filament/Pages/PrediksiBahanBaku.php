<?php

namespace App\Filament\Pages;

use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class PrediksiBahanBaku extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-beaker';

    protected static string|\UnitEnum|null $navigationGroup = 'Analitik';

    protected static ?string $navigationLabel = 'Prediksi Bahan Baku';

    protected static ?string $title = 'Prediksi Penggunaan Bahan Baku';

    protected static ?int $navigationSort = 14;

    // ── Input rentang tanggal dari admin ───────────────────────────────
    public string $inputDateFrom  = '';
    public string $inputDateTo    = '';
    public string $dateRangeError = '';

    // ── State ──────────────────────────────────────────────────────────
    public bool    $hasResult = false;
    public ?string $lastRunAt = null;
    public ?string $errorMsg  = null;

    // ── Hasil prediksi ─────────────────────────────────────────────────
    public int    $totalIngredients    = 0;
    public int    $forecastDays        = 0;
    public string $dateFrom            = '';
    public string $dateTo              = '';
    public string $dateForecastFrom    = '';
    public string $dateForecastTo      = '';
    public array  $predictions         = [];
    public array  $summaryTable        = [];
    public array  $preprocessLogs      = [];

    // ── Grafik (base64 PNG) ────────────────────────────────────────────
    public ?string $chartForecastAll       = null;
    public ?string $chartFeatureImportance = null;
    public ?string $chartEvaluation        = null;
    public ?string $chartAllItems          = null;
    public array   $chartPerIngredient     = [];

    public function getView(): string
    {
        return 'filament.pages.prediksi-bahan-baku';
    }

    public function getTitle(): string
    {
        return 'Prediksi Penggunaan Bahan Baku';
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

    // ── Panggil FastAPI endpoint prediksi bahan baku ───────────────────
    public function runPrediction(): void
    {
        $this->errorMsg       = null;
        $this->dateRangeError = '';

        if (! $this->inputDateFrom || ! $this->inputDateTo) {
            $this->dateRangeError = 'Harap isi rentang tanggal data penggunaan bahan baku terlebih dahulu.';
            return;
        }

        $from = Carbon::parse($this->inputDateFrom);
        $to   = Carbon::parse($this->inputDateTo);

        if ($to->lessThanOrEqualTo($from)) {
            $this->dateRangeError = 'Tanggal akhir harus lebih besar dari tanggal awal.';
            return;
        }

        if ($from->diffInMonths($to) < 3) {
            $this->dateRangeError = 'Rentang tanggal data penggunaan bahan baku minimal 3 bulan.';
            return;
        }

        try {
            $response = Http::timeout(600)->asJson()->post('http://127.0.0.1:8001/prediction-bahan-baku', [
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

            $this->totalIngredients = $data['total_ingredients']       ?? 0;
            $this->forecastDays     = $data['forecast_days']           ?? 0;
            $this->dateFrom         = $data['date_range']['from']      ?? '';
            $this->dateTo           = $data['date_range']['to']        ?? '';
            $this->dateForecastFrom = $data['forecast_range']['from']  ?? '';
            $this->dateForecastTo   = $data['forecast_range']['to']    ?? '';
            $this->predictions      = $data['predictions']             ?? [];
            $this->summaryTable     = $data['summary_table']           ?? [];
            $this->preprocessLogs   = $data['preprocessing_logs']      ?? [];

            $charts = $data['charts'] ?? [];
            $this->chartForecastAll       = $charts['forecast_all']       ?? null;
            $this->chartFeatureImportance = $charts['feature_importance'] ?? null;
            $this->chartEvaluation        = $charts['evaluation']         ?? null;
            $this->chartAllItems          = $charts['all_items']          ?? null;
            $this->chartPerIngredient     = $charts['per_ingredient']     ?? [];

            $this->hasResult = true;
            $this->lastRunAt = now()->locale('id')->translatedFormat('d M Y, H:i');

            // ── Simpan ke history (max 3, unik per date range) ──────────────
            $history   = Cache::get('prediksi_bahan_baku_results_history', []);
            $inputFrom = $this->inputDateFrom;
            $inputTo   = $this->inputDateTo;

            // Hapus entry dengan date range yang sama (replace)
            $history = array_values(array_filter(
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
                'total_ingredients'    => $this->totalIngredients,
                'forecast_days'        => $this->forecastDays,
                'predictions'          => $this->predictions,
                'summary_table'        => $this->summaryTable,
                'chart_feature_importance' => $this->chartFeatureImportance,
            ]);

            Cache::put('prediksi_bahan_baku_results_history', $history, now()->addDays(30));

            Notification::make()
                ->title('Prediksi Bahan Baku selesai!')
                ->body("Berhasil memprediksi {$this->totalIngredients} bahan baku untuk {$this->forecastDays} hari ke depan.")
                ->success()
                ->send();

        } catch (\Exception $e) {
            $this->errorMsg  = $e->getMessage();
            $this->hasResult = false;

            Notification::make()
                ->title('Prediksi Bahan Baku gagal')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('run_prediction_bahan_baku')
                ->label('Jalankan Prediksi Bahan Baku')
                ->icon('heroicon-o-sparkles')
                ->color('primary')
                ->disabled(fn() => ! $this->isDateRangeValid())
                ->action($this->runPrediction(...)),
        ];
    }
}
