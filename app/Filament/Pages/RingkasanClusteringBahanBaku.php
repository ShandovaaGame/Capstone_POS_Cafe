<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;

class RingkasanClusteringBahanBaku extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-beaker';

    protected static string|\UnitEnum|null $navigationGroup = 'Analitik';

    protected static ?string $navigationLabel = 'Ringkasan Klasterisasi Bahan Baku';

    protected static ?string $title = 'Ringkasan Klasterisasi Bahan Baku';

    protected static ?int $navigationSort = 10;

    // ── State: array maks 3 hasil terakhir ────────────────────────────
    public array   $results   = [];
    public bool    $hasResult = false;
    public ?string $errorMsg  = null;

    public function getView(): string
    {
        return 'filament.pages.ringkasan-clustering-bahan-baku';
    }

    public function getTitle(): string
    {
        return 'Ringkasan Klasterisasi Bahan Baku';
    }

    public function mount(): void
    {
        $this->loadFromCache();
    }

    public function loadFromCache(): void
    {
        $history = Cache::get('klasterisasi_bahan_baku_results_history', []);
        if (empty($history)) {
            $this->results   = [];
            $this->hasResult = false;
            return;
        }
        $this->results   = $history;
        $this->hasResult = true;
    }

    public function refreshData(): void
    {
        $this->errorMsg = null;
        $history = Cache::get('klasterisasi_bahan_baku_results_history', []);

        if (empty($history)) {
            $this->errorMsg  = 'Belum ada hasil clustering. Silakan jalankan Klasterisasi Bahan Baku terlebih dahulu.';
            $this->hasResult = false;

            Notification::make()
                ->title('Belum ada data')
                ->body('Jalankan proses di halaman Klasterisasi Bahan Baku dulu.')
                ->warning()
                ->send();
            return;
        }

        $this->loadFromCache();

        $latestRunAt = $this->results[0]['run_at'] ?? '-';
        Notification::make()
            ->title('Data diperbarui')
            ->body('Menampilkan ' . count($this->results) . ' hasil klasterisasi terakhir. Terbaru: ' . $latestRunAt)
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh_data')
                ->label('Perbarui Data Klasterisasi Bahan Baku')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->action(fn () => $this->refreshData()),
        ];
    }
}
