<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;

class PredictionRingBahanBaku extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-beaker';

    protected static string|\UnitEnum|null $navigationGroup = 'Analitik';

    protected static ?string $navigationLabel = 'Prediction Ring Bahan Baku';

    protected static ?string $title = 'Prediction Ring Bahan Baku';

    protected static ?int $navigationSort = 15;

    // ── State ──────────────────────────────────────────────────────────
    public bool  $hasResult = false;

    // Menyimpan maks. 3 hasil prediksi terakhir (rentang tanggal berbeda)
    public array $results = [];

    public function getView(): string
    {
        return 'filament.pages.prediction-ring-bahan-baku';
    }

    public function getTitle(): string
    {
        return 'Prediction Ring Bahan Baku';
    }

    // ── Load saat halaman pertama kali dibuka ──────────────────────────
    public function mount(): void
    {
        $this->loadFromCache();
    }

    // ── Ambil history dari cache ───────────────────────────────────────
    public function loadFromCache(): void
    {
        $history = Cache::get('prediksi_bahan_baku_results_history', []);

        if (empty($history)) {
            $this->hasResult = false;
            $this->results   = [];
            return;
        }

        $this->results   = $history;
        $this->hasResult = true;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Perbarui Data Prediksi Penggunaan Bahan Baku')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    $this->loadFromCache();

                    if ($this->hasResult) {
                        $count = count($this->results);
                        Notification::make()
                            ->title('Data diperbarui')
                            ->body("Menampilkan {$count} laporan prediksi bahan baku terakhir.")
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Belum ada data prediksi')
                            ->body('Silakan jalankan prediksi terlebih dahulu di halaman Prediksi Bahan Baku.')
                            ->warning()
                            ->send();
                    }
                }),
        ];
    }
}
