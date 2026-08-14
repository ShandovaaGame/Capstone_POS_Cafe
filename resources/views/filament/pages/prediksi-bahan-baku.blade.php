<x-filament-panels::page>

{{-- ════════════════════════════════════════════════════════════════════ --}}
{{-- INPUT RENTANG TANGGAL DATA PENGGUNAAN BAHAN BAKU                     --}}
{{-- ════════════════════════════════════════════════════════════════════ --}}
<div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 mb-6 shadow-sm overflow-hidden">

    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60">
        <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">Rentang Tanggal Data Penggunaan Bahan Baku</p>
        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Tentukan rentang tanggal riwayat penggunaan bahan baku yang akan digunakan (minimal 3 bulan)</p>
    </div>

    <div class="px-6 py-5">
        <div style="border: 2px solid #3b82f6; border-radius: 10px; padding: 16px 20px; background: rgba(59,130,246,0.04);">
            <div class="flex flex-col sm:flex-row items-stretch sm:items-end gap-3">

                <div class="flex-1">
                    <label style="display:block; font-size:0.72rem; font-weight:700; color:#3b82f6; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:8px;">
                        Dari Tanggal
                    </label>
                    <input type="date"
                           wire:model.live="inputDateFrom"
                           class="block w-full rounded-lg border border-gray-300 dark:border-gray-600
                                  bg-white dark:bg-gray-900 text-gray-900 dark:text-white
                                  px-4 py-2.5 text-sm shadow-sm
                                  focus:border-primary-500 focus:ring-1 focus:ring-primary-500
                                  transition" />
                </div>

                <div class="hidden sm:flex items-center justify-center pb-0.5">
                    <span style="color:#3b82f6; font-size:0.875rem; font-weight:700;">s/d</span>
                </div>

                <div class="flex-1">
                    <label style="display:block; font-size:0.72rem; font-weight:700; color:#3b82f6; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:8px;">
                        Sampai Tanggal
                    </label>
                    <input type="date"
                           wire:model.live="inputDateTo"
                           class="block w-full rounded-lg border border-gray-300 dark:border-gray-600
                                  bg-white dark:bg-gray-900 text-gray-900 dark:text-white
                                  px-4 py-2.5 text-sm shadow-sm
                                  focus:border-primary-500 focus:ring-1 focus:ring-primary-500
                                  transition" />
                </div>

            </div>
        </div>

        <div class="mt-4">
            @if(! $inputDateFrom && ! $inputDateTo)
                <div class="rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 px-4 py-3">
                    <p class="text-xs text-amber-700 dark:text-amber-300">
                        Pilih <strong>Dari Tanggal</strong> dan <strong>Sampai Tanggal</strong> dengan rentang minimal <strong>3 bulan</strong>, lalu tekan <strong>"Jalankan Prediksi Bahan Baku"</strong> di kanan atas.
                    </p>
                </div>
            @elseif($dateRangeError)
                <div class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-4 py-3">
                    <p class="text-xs text-red-700 dark:text-red-300 font-medium">{{ $dateRangeError }}</p>
                </div>
            @elseif($this->isDateRangeValid())
                <div class="rounded-lg bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 px-4 py-3">
                    <p class="text-xs font-medium text-emerald-700 dark:text-emerald-300">
                        Rentang tanggal valid: <strong>{{ \Carbon\Carbon::parse($inputDateFrom)->translatedFormat('d M Y') }}</strong> s/d <strong>{{ \Carbon\Carbon::parse($inputDateTo)->translatedFormat('d M Y') }}</strong>.
                        Tekan <strong>"Jalankan Prediksi Bahan Baku"</strong> di kanan atas.
                    </p>
                </div>
            @else
                <div class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-4 py-3">
                    <p class="text-xs text-red-700 dark:text-red-300 font-medium">Rentang tanggal data penggunaan bahan baku minimal 3 bulan.</p>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- ── Error FastAPI ─────────────────────────────────────────────────── --}}
@if($errorMsg)
    <div class="rounded-xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 p-4 mb-6">
        <p class="text-sm font-semibold text-red-700 dark:text-red-300">Prediksi gagal</p>
        <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $errorMsg }}</p>
        <p class="text-xs text-red-500 mt-2">
            Pastikan FastAPI sudah berjalan:
            <code class="bg-red-100 dark:bg-red-900 px-1.5 py-0.5 rounded font-mono">
                uvicorn datamining.api:app --port 8001 --reload
            </code>
        </p>
    </div>
@endif

{{-- ── Belum ada hasil ─────────────────────────────────────────────────── --}}
@if(! $hasResult && ! $errorMsg)
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-10 text-center">
        <p class="text-base font-semibold text-gray-700 dark:text-gray-200 mb-2">Belum Ada Hasil Prediksi Bahan Baku</p>
        <p class="text-sm text-gray-400 max-w-md mx-auto">
            Isi rentang tanggal di atas (minimal 3 bulan), lalu tekan
            <span class="font-semibold text-primary-600">"Jalankan Prediksi Bahan Baku"</span>
            di kanan atas untuk memulai analisis.
        </p>
    </div>
@endif

{{-- ════════════════════════════════════════════════════════════════════ --}}
{{-- HASIL PREDIKSI                                                        --}}
{{-- ════════════════════════════════════════════════════════════════════ --}}
@if($hasResult)

    {{-- Meta bar --}}
    <div class="rounded-xl border border-primary-200 dark:border-primary-800 bg-primary-50 dark:bg-primary-900/20 px-5 py-3 mb-6 flex flex-wrap gap-x-6 gap-y-1 text-xs">
        <span class="text-gray-500 dark:text-gray-400">
            Dijalankan:
            <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $lastRunAt }}</span>
        </span>
        <span class="text-gray-500 dark:text-gray-400">
            Data yang digunakan:
            <span class="font-semibold text-gray-700 dark:text-gray-200">
                {{ \Carbon\Carbon::parse($inputDateFrom)->translatedFormat('d M Y') }}
                s/d
                {{ \Carbon\Carbon::parse($inputDateTo)->translatedFormat('d M Y') }}
            </span>
        </span>
        <span class="text-gray-500 dark:text-gray-400">
            Periode prediksi:
            <span class="font-semibold text-primary-600 dark:text-primary-400">{{ $dateForecastFrom }} s/d {{ $dateForecastTo }}</span>
        </span>
        <span class="text-gray-500 dark:text-gray-400">
            <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $totalIngredients }}</span> bahan baku ·
            <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $forecastDays }}</span> hari prediksi
        </span>
    </div>

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- OUTPUT 1 — RINGKASAN TABEL SEMUA BAHAN BAKU                    --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    @if(count($summaryTable))
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 mb-6 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-sm font-bold text-gray-900 dark:text-white">Output 1 — Ringkasan Prediksi Semua Bahan Baku</h2>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                Periode prediksi: <span class="font-medium">{{ $dateForecastFrom }}</span> s/d <span class="font-medium">{{ $dateForecastTo }}</span>
                — diurutkan berdasarkan total prediksi tertinggi
            </p>
        </div>
        <div class="overflow-x-auto">
            <table style="min-width:800px; width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background-color:#1e40af;">
                        <th style="padding:11px 14px; text-align:right;  font-size:0.7rem; font-weight:600; color:#ffffff; text-transform:uppercase; letter-spacing:0.06em; white-space:nowrap;">No</th>
                        <th style="padding:11px 14px; text-align:left;   font-size:0.7rem; font-weight:600; color:#ffffff; text-transform:uppercase; letter-spacing:0.06em; white-space:nowrap;">Nama Bahan Baku</th>
                        <th style="padding:11px 14px; text-align:center; font-size:0.7rem; font-weight:600; color:#ffffff; text-transform:uppercase; letter-spacing:0.06em; white-space:nowrap;">Satuan</th>
                        <th style="padding:11px 14px; text-align:right;  font-size:0.7rem; font-weight:600; color:#ffffff; text-transform:uppercase; letter-spacing:0.06em; white-space:nowrap;">Total Prediksi</th>
                        <th style="padding:11px 14px; text-align:right;  font-size:0.7rem; font-weight:600; color:#ffffff; text-transform:uppercase; letter-spacing:0.06em; white-space:nowrap;">Rata-rata/Hari</th>
                        <th style="padding:11px 14px; text-align:right;  font-size:0.7rem; font-weight:600; color:#ffffff; text-transform:uppercase; letter-spacing:0.06em; white-space:nowrap;">MAE</th>
                        <th style="padding:11px 14px; text-align:right;  font-size:0.7rem; font-weight:600; color:#ffffff; text-transform:uppercase; letter-spacing:0.06em; white-space:nowrap;">RMSE</th>
                        <th style="padding:11px 14px; text-align:right;  font-size:0.7rem; font-weight:600; color:#ffffff; text-transform:uppercase; letter-spacing:0.06em; white-space:nowrap;">MAPE (%)</th>
                        <th style="padding:11px 14px; text-align:right;  font-size:0.7rem; font-weight:600; color:#ffffff; text-transform:uppercase; letter-spacing:0.06em; white-space:nowrap;">SMAPE (%)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($summaryTable as $i => $row)
                    @php $mape = $row['mape'] ?? 0; @endphp
                    <tr style="background-color:{{ $i % 2 === 0 ? '#ffffff' : '#f8fafc' }}; border-bottom:1px solid #e2e8f0;">
                        <td style="padding:11px 14px; text-align:right;  font-size:0.8rem; color:#64748b;">{{ $i + 1 }}</td>
                        <td style="padding:11px 14px; text-align:left;   font-size:0.875rem; font-weight:600; color:#1e40af;">{{ $row['nama_bahan_baku'] }}</td>
                        <td style="padding:11px 14px; text-align:center; font-size:0.8rem; color:#64748b;">
                            <span style="display:inline-block; background:#e0e7ff; color:#3730a3; padding:2px 10px; border-radius:9999px; font-size:0.75rem; font-weight:600;">{{ $row['satuan'] ?? '-' }}</span>
                        </td>
                        <td style="padding:11px 14px; text-align:right;  font-size:0.875rem; font-weight:700; color:#4338ca;">{{ number_format($row['total_forecast'] ?? 0, 1) }}</td>
                        <td style="padding:11px 14px; text-align:right;  font-size:0.875rem; color:#374151;">{{ number_format($row['avg_per_day'] ?? 0, 1) }}</td>
                        <td style="padding:11px 14px; text-align:right;  font-size:0.875rem; color:#374151;">{{ number_format($row['mae'] ?? 0, 2) }}</td>
                        <td style="padding:11px 14px; text-align:right;  font-size:0.875rem; color:#374151;">{{ number_format($row['rmse'] ?? 0, 2) }}</td>
                        <td style="padding:11px 14px; text-align:right;  font-size:0.875rem; font-weight:600;
                            color:{{ $mape <= 10 ? '#16a34a' : ($mape <= 25 ? '#d97706' : '#dc2626') }};">
                            {{ number_format($mape, 2) }}%
                        </td>
                        <td style="padding:11px 14px; text-align:right;  font-size:0.875rem; color:#374151;">{{ number_format($row['smape'] ?? 0, 2) }}%</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- OUTPUT 2 — GRAFIK TOTAL FORECAST                               --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    @if($chartForecastAll)
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 mb-6 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-sm font-bold text-gray-900 dark:text-white">Output 2 — Total Prediksi Penggunaan per Bahan Baku</h2>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Perbandingan jumlah prediksi penggunaan seluruh bahan baku dalam 2 hari ke depan</p>
        </div>
        <div class="p-5">
            <img src="data:image/png;base64,{{ $chartForecastAll }}" alt="Forecast All" class="w-full rounded-lg border border-gray-100 dark:border-gray-700"/>
        </div>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- OUTPUT 3 — DETAIL PREDIKSI PER BAHAN BAKU                     --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    @if(count($predictions))

    {{-- Header Output 3 --}}
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 mb-4 overflow-hidden">
        <div class="px-6 py-4">
            <h2 class="text-sm font-bold text-gray-900 dark:text-white">Output 3 — Detail Prediksi Penggunaan per Bahan Baku</h2>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                Prediksi 2 hari ke depan: <span class="font-medium">{{ $dateForecastFrom }}</span> s/d <span class="font-medium">{{ $dateForecastTo }}</span>
                — satu kartu per bahan baku
            </p>
        </div>
    </div>

    {{-- Satu card per bahan baku --}}
    @foreach($predictions as $pred)
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 mb-4 overflow-hidden">

        {{-- Header bahan baku --}}
        <div class="px-6 py-3 bg-blue-50 dark:bg-blue-900/20 border-b border-blue-100 dark:border-blue-800/40 flex flex-wrap items-center gap-3">
            <span style="font-weight:700; font-size:0.875rem; color:#1e40af;">{{ $pred['nama_bahan_baku'] }}</span>
            @if(!empty($pred['satuan']))
            <span style="display:inline-block; background:#e0e7ff; color:#3730a3; padding:1px 10px; border-radius:9999px; font-size:0.75rem; font-weight:600;">
                {{ $pred['satuan'] }}
            </span>
            @endif
            <span style="font-size:0.78rem; color:#374151;">
                Total: <span style="font-weight:700; color:#4338ca;">{{ number_format($pred['total_forecast'] ?? 0, 1) }} {{ $pred['satuan'] ?? '' }}</span>
            </span>
            <span style="margin-left:auto; display:flex; flex-wrap:wrap; gap:12px; font-size:0.78rem; color:#64748b;">
                <span>MAE: <span style="font-weight:600; color:#374151;">{{ number_format($pred['mae'] ?? 0, 2) }}</span></span>
                <span>RMSE: <span style="font-weight:600; color:#374151;">{{ number_format($pred['rmse'] ?? 0, 2) }}</span></span>
                <span>MAPE: <span style="font-weight:600;
                    color:{{ ($pred['mape'] ?? 0) <= 10 ? '#16a34a' : (($pred['mape'] ?? 0) <= 25 ? '#d97706' : '#dc2626') }};">
                    {{ number_format($pred['mape'] ?? 0, 2) }}%
                </span></span>
                <span>SMAPE: <span style="font-weight:600; color:#374151;">{{ number_format($pred['smape'] ?? 0, 2) }}%</span></span>
            </span>
        </div>

        {{-- Tabel forecast harian --}}
        @if(count($pred['forecast'] ?? []))
        <div class="overflow-x-auto">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background-color:#1e40af;">
                        <th style="padding:10px 20px; text-align:left;  font-size:0.7rem; font-weight:600; color:#ffffff; text-transform:uppercase; letter-spacing:0.06em; white-space:nowrap;">Tanggal</th>
                        <th style="padding:10px 20px; text-align:left;  font-size:0.7rem; font-weight:600; color:#ffffff; text-transform:uppercase; letter-spacing:0.06em; white-space:nowrap;">Hari</th>
                        <th style="padding:10px 20px; text-align:center;font-size:0.7rem; font-weight:600; color:#ffffff; text-transform:uppercase; letter-spacing:0.06em; white-space:nowrap;">Tipe Hari</th>
                        <th style="padding:10px 20px; text-align:right; font-size:0.7rem; font-weight:600; color:#ffffff; text-transform:uppercase; letter-spacing:0.06em; white-space:nowrap;">Prediksi</th>
                        <th style="padding:10px 20px; text-align:right; font-size:0.7rem; font-weight:600; color:#ffffff; text-transform:uppercase; letter-spacing:0.06em; white-space:nowrap;">Batas Bawah</th>
                        <th style="padding:10px 20px; text-align:right; font-size:0.7rem; font-weight:600; color:#ffffff; text-transform:uppercase; letter-spacing:0.06em; white-space:nowrap;">Batas Atas</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pred['forecast'] as $di => $day)
                    <tr style="background-color:{{ $di % 2 === 0 ? '#ffffff' : '#f8fafc' }}; border-bottom:1px solid #e2e8f0;">
                        <td style="padding:10px 20px; font-family:monospace; font-size:0.8rem; color:#374151;">{{ $day['tanggal'] }}</td>
                        <td style="padding:10px 20px; font-size:0.875rem; color:#374151;">{{ $day['hari'] }}</td>
                        <td style="padding:10px 20px; text-align:center;">
                            @if(($day['day_type'] ?? '') === 'Weekend')
                                <span style="display:inline-block; background:#fef9c3; color:#92400e; padding:2px 12px; border-radius:9999px; font-size:0.75rem; font-weight:600;">Weekend</span>
                            @else
                                <span style="display:inline-block; background:#dbeafe; color:#1d4ed8; padding:2px 12px; border-radius:9999px; font-size:0.75rem; font-weight:600;">Weekday</span>
                            @endif
                        </td>
                        <td style="padding:10px 20px; text-align:right;">
                            <span style="display:inline-block; background:#e0e7ff; color:#3730a3; padding:3px 14px; border-radius:9999px; font-size:0.875rem; font-weight:700;">
                                {{ number_format($day['prediksi'] ?? 0, 1) }}
                            </span>
                        </td>
                        <td style="padding:10px 20px; text-align:right; font-size:0.875rem; color:#6b7280;">{{ number_format($day['batas_bawah'] ?? 0, 1) }}</td>
                        <td style="padding:10px 20px; text-align:right; font-size:0.875rem; color:#6b7280;">{{ number_format($day['batas_atas'] ?? 0, 1) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

    </div>
    @endforeach

    <div class="mb-4"></div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- OUTPUT 4 — EVALUASI MODEL 2×2                                  --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    @if($chartEvaluation)
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 mb-6 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-sm font-bold text-gray-900 dark:text-white">Output 4 — Evaluasi Model per Bahan Baku</h2>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">MAE, RMSE, MAPE, dan SMAPE dihitung pada data uji (25% terakhir dari data historis)</p>
        </div>
        <div class="p-5">
            <img src="data:image/png;base64,{{ $chartEvaluation }}" alt="Evaluation Chart" class="w-full rounded-lg border border-gray-100 dark:border-gray-700"/>
        </div>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- OUTPUT 5 — ANALISIS RATA-RATA WEEKDAY VS WEEKEND               --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    @if($chartFeatureImportance)
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 mb-6 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-sm font-bold text-gray-900 dark:text-white">Output 5 — Analisis Rata-rata Jumlah Penggunaan Bahan Baku: Weekday vs Weekend</h2>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                Perbandingan rata-rata jumlah penggunaan tiap bahan baku pada hari kerja dan akhir pekan berdasarkan data historis yang digunakan.
                Memperlihatkan seberapa besar perbedaan pengaruh weekend dan weekday terhadap penggunaan tiap bahan baku.
            </p>
        </div>
        <div class="p-5">
            <img src="data:image/png;base64,{{ $chartFeatureImportance }}" alt="Weekday vs Weekend" class="w-full rounded-lg border border-gray-100 dark:border-gray-700"/>
        </div>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- OUTPUT 6 — PREDIKSI VS AKTUAL SEMUA BAHAN BAKU                 --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    @if($chartAllItems)
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 mb-6 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-sm font-bold text-gray-900 dark:text-white">Output 6 — Prediksi vs Aktual — Semua Bahan Baku</h2>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Grafik gabungan seluruh bahan baku: garis training, aktual test, dan prediksi test</p>
        </div>
        <div class="p-5">
            <img src="data:image/png;base64,{{ $chartAllItems }}" alt="All Ingredients" class="w-full rounded-lg border border-gray-100 dark:border-gray-700"/>
        </div>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- OUTPUT 7 — PREDIKSI VS AKTUAL PER BAHAN BAKU                   --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    @if(count($chartPerIngredient))
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 mb-6 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-sm font-bold text-gray-900 dark:text-white">Output 7 — Prediksi vs Aktual per Bahan Baku</h2>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Grafik individual tiap bahan baku: perbandingan data training, aktual test, dan prediksi test (25%)</p>
        </div>
        <div class="divide-y divide-gray-100 dark:divide-gray-700/40">
            @foreach($chartPerIngredient as $item)
            <div class="p-5">
                <p class="text-xs font-semibold text-gray-600 dark:text-gray-400 mb-3">{{ $item['nama'] }}</p>
                <img src="data:image/png;base64,{{ $item['chart'] }}" alt="Chart {{ $item['nama'] }}" class="w-full rounded-lg border border-gray-100 dark:border-gray-700"/>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- PROSES — LOG PREPROCESSING (dipindah ke bawah)                 --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    @if(count($preprocessLogs))
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 mb-6 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-sm font-bold text-gray-900 dark:text-white">Proses — Tahapan Preprocessing Data</h2>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Tahapan persiapan dan transformasi data sebelum model Prophet dilatih</p>
        </div>
        <div class="divide-y divide-gray-50 dark:divide-gray-700/30">
            @foreach($preprocessLogs as $i => $log)
            <div class="flex items-start gap-4 px-6 py-4">
                <span class="shrink-0 w-6 h-6 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300 text-xs font-bold flex items-center justify-center mt-0.5">
                    {{ $i + 1 }}
                </span>
                <div>
                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $log['tahap'] }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $log['detail'] }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

@endif {{-- end hasResult --}}

</x-filament-panels::page>
