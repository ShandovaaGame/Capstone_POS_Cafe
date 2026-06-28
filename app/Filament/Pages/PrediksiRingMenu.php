<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;

class PrediksiRingMenu extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string|\UnitEnum|null $navigationGroup = 'Analitik';

    protected static ?string $navigationLabel = 'Prediksi Ring Menu';

    protected static ?string $title = 'Prediksi Ring Menu';

    protected static ?int $navigationSort = 10;

    // ── State ──────────────────────────────────────────────────────────
    public bool    $hasResult = false;
    public ?string $errorMsg  = null;

    // Menyimpan maks. 3 hasil prediksi terakhir (rentang tanggal berbeda)
    public array $results = [];

    public function getView(): string
    {
        return 'filament.pages.prediksi-ring-menu';
    }

    public function getTitle(): string
    {
        return 'Prediksi Ring Menu';
    }

    // ── Load saat halaman pertama kali dibuka ──────────────────────────
    public function mount(): void
    {
        $this->loadFromCache();
    }

    // ── Ambil history dari cache ───────────────────────────────────────
    public function loadFromCache(): void
    {
        $history = Cache::get('prediksi_menu_results_history', []);

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
                ->label('Perbarui Data Prediksi Penjualan Menu')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    $this->loadFromCache();

                    if ($this->hasResult) {
                        $count = count($this->results);
                        Notification::make()
                            ->title('Data diperbarui')
                            ->body("Menampilkan {$count} laporan prediksi menu terakhir.")
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Belum ada data prediksi')
                            ->body('Silakan jalankan prediksi terlebih dahulu di halaman Prediksi Menu.')
                            ->warning()
                            ->send();
                    }
                }),
        ];
    }
}
