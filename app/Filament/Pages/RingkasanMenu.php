<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;

class RingkasanMenu extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string|\UnitEnum|null $navigationGroup = 'Analitik';

    protected static ?string $navigationLabel = 'Ringkasan Menu';

    protected static ?string $title = 'Ringkasan Menu';

    protected static ?int $navigationSort = 10;

    // ── State ──────────────────────────────────────────────────────────────
    public bool    $hasResult = false;
    public ?string $errorMsg  = null;

    /** @var array<int, array<string, mixed>> */
    public array $results = [];

    public function getView(): string
    {
        return 'filament.pages.ringkasan-menu';
    }

    public function getTitle(): string
    {
        return 'Ringkasan Menu';
    }

    public function mount(): void
    {
        $this->loadFromCache();
    }

    public function refreshClusteringData(): void
    {
        $this->errorMsg = null;
        $cached = Cache::get('klasterisasi_menu_results', []);

        if (empty($cached)) {
            $this->errorMsg  = 'Belum ada hasil clustering. Jalankan proses di halaman Klasterisasi Menu Penjualan terlebih dahulu.';
            $this->hasResult = false;

            Notification::make()
                ->title('Belum ada data')
                ->body('Jalankan proses di halaman Klasterisasi Menu Penjualan dulu.')
                ->warning()
                ->send();
            return;
        }

        $this->loadFromCache();

        $latestRunAt = $this->results[0]['last_run_at'] ?? '-';
        Notification::make()
            ->title('Data diperbarui')
            ->body("Menampilkan {$this->getResultCount()} hasil clustering. Terbaru: {$latestRunAt}")
            ->success()
            ->send();
    }

    public function getResultCount(): int
    {
        return count($this->results);
    }

    private function loadFromCache(): void
    {
        $cached          = Cache::get('klasterisasi_menu_results', []);
        $this->results   = $cached;
        $this->hasResult = count($cached) > 0;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh_clustering')
                ->label('Perbarui Data Klasterisasi Menu')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->action(fn () => $this->refreshClusteringData()),
        ];
    }
}
