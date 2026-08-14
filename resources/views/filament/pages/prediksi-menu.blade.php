<x-filament-panels::page>

{{-- ════════════════════════════════════════════════════════════════════ --}}
{{-- INPUT RENTANG TANGGAL DATA PENJUALAN                                 --}}
{{-- ════════════════════════════════════════════════════════════════════ --}}
<div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 mb-6 shadow-sm overflow-hidden">

    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60">
        <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">Rentang Tanggal Data Penjualan</p>
        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Pilih periode data transaksi yang akan digunakan sebagai dasar prediksi (minimal 3 bulan)</p>
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
            @if(! $inputDateFrom || ! $inputDateTo)
                <div class="rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 px-4 py-3">
                    <p class="text-xs text-amber-700 dark:text-amber-300">
                        Pilih <strong>Dari Tanggal</strong> dan <strong>Sampai Tanggal</strong> dengan rentang minimal <strong>3 bulan</strong>, lalu tekan <strong>"Jalankan Prediksi"</strong> di kanan atas.
                    </p>
                </div>
            @elseif($dateRangeError)
                <div class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-4 py-3">
                    <p class="text-xs text-red-700 dark:text-red-300 font-medium">{{ $dateRangeError }}</p>
                </div>
            @else
                <div class="rounded-lg bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 px-4 py-3">
                    <p class="text-xs font-medium text-emerald-700 dark:text-emerald-300">
                        Rentang tanggal valid: <strong>{{ \Carbon\Carbon::parse($inputDateFrom)->translatedFormat('d M Y') }}</strong> s/d <strong>{{ \Carbon\Carbon::parse($inputDateTo)->translatedFormat('d M Y') }}</strong>.
                        Tekan <strong>"Jalankan Prediksi"</strong> di kanan atas.
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- ── Error dari FastAPI ─────────────────────────────────────────────── --}}
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

{{-- ── Belum ada hasil ────────────────────────────────────────────────── --}}
@if(! $hasResult && ! $errorMsg)
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-10 text-center">
        <p class="text-base font-semibold text-gray-700 dark:text-gray-200">Belum Ada Hasil Prediksi</p>
        <p class="text-sm text-gray-400 mt-2">
            Isi rentang tanggal di atas, lalu tekan
            <span class="font-semibold text-primary-600">"Jalankan Prediksi"</span> untuk memulai analisis.
        </p>
    </div>
@endif

{{-- ════════════════════════════════════════════════════════════════════ --}}
{{-- HASIL PREDIKSI                                                       --}}
{{-- ════════════════════════════════════════════════════════════════════ --}}
@if($hasResult)

    {{-- Meta bar --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6 px-1">
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-primary-50 dark:bg-primary-900/30 border border-primary-200 dark:border-primary-700 px-3 py-1.5 text-xs font-medium text-primary-700 dark:text-primary-300">
                Dijalankan: {{ $lastRunAt }}
            </span>
        </div>
        <div class="flex flex-wrap items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
            <span>
                Data penjualan:
                <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $dateFrom }}</span>
                s/d
                <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $dateTo }}</span>
            </span>
            <span class="text-gray-300 dark:text-gray-600">·</span>
            <span>
                Periode prediksi:
                <span class="font-semibold text-primary-600 dark:text-primary-400">{{ $dateForecastFrom }}</span>
                s/d
                <span class="font-semibold text-primary-600 dark:text-primary-400">{{ $dateForecastTo }}</span>
            </span>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- PROSES 2 — EVALUASI MODEL (MAE saja)                           --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    @if(count($summaryTable))
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 mb-6 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-base font-bold text-gray-900 dark:text-white">Proses 2 — Evaluasi Model per Menu</h2>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                Model dilatih pada data train (75%) dan dievaluasi pada data test (25%)
            </p>
        </div>
        <div class="overflow-x-auto">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background-color:#1e40af;">
                        <th style="padding:12px 20px; text-align:left; font-size:0.75rem; font-weight:600; color:#ffffff; text-transform:uppercase; letter-spacing:0.06em;">Nama Menu</th>
                        <th style="padding:12px 20px; text-align:center; font-size:0.75rem; font-weight:600; color:#ffffff; text-transform:uppercase; letter-spacing:0.06em;">Model</th>
                        <th style="padding:12px 20px; text-align:right; font-size:0.75rem; font-weight:600; color:#ffffff; text-transform:uppercase; letter-spacing:0.06em;">MAE</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($summaryTable as $i => $row)
                    <tr style="background-color:{{ $i % 2 === 0 ? '#ffffff' : '#f8fafc' }}; border-bottom:1px solid #e2e8f0;">
                        <td style="padding:11px 20px; font-size:0.875rem; font-weight:600; color:#1e40af;">
                            {{ $row['nama_menu'] }}
                        </td>
                        <td style="padding:11px 20px; text-align:center; font-size:0.75rem; color:#6b7280;">
                            <span style="display:inline-block; background:#ede9fe; color:#7c3aed; padding:2px 10px; border-radius:9999px; font-weight:600;">
                                {{ $row['model'] ?? 'Prophet' }}
                            </span>
                        </td>
                        <td style="padding:11px 20px; text-align:right; font-size:0.875rem; font-weight:700; color:#1e293b;">
                            {{ isset($row['mae']) ? number_format($row['mae'], 2) : '-' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-6 py-3 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/20">
            <p class="text-xs text-gray-400">
                <span class="font-semibold text-gray-500 dark:text-gray-300">MAE</span>
                — Mean Absolute Error: rata-rata selisih absolut antara nilai aktual dan prediksi pada data test.
                Semakin kecil nilainya, semakin akurat model.
            </p>
        </div>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- OUTPUT 1 — RINGKASAN TOTAL PREDIKSI (bar chart)                --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    @if($chartForecastAll)
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 mb-6 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-base font-bold text-gray-900 dark:text-white">Output 1 — Ringkasan Total Prediksi Penjualan</h2>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                Total prediksi unit penjualan per menu untuk periode
                <span class="font-medium text-primary-600 dark:text-primary-400">{{ $dateForecastFrom }} s/d {{ $dateForecastTo }}</span>
            </p>
        </div>
        <div class="p-5">
            <img src="data:image/png;base64,{{ $chartForecastAll }}"
                 alt="Ringkasan Total Prediksi" class="w-full rounded-lg"/>
        </div>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- OUTPUT 2 — PREDIKSI PENJUALAN 2 HARI KE DEPAN (per menu)      --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    @if(count($predictions))

    {{-- Header Output 2 --}}
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 mb-4 overflow-hidden">
        <div class="px-6 py-4">
            <h2 class="text-base font-bold text-gray-900 dark:text-white">Output 2 — Prediksi Penjualan 2 Hari ke Depan</h2>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                Periode prediksi:
                <span class="font-medium text-primary-600 dark:text-primary-400">{{ $dateForecastFrom }}</span>
                s/d
                <span class="font-medium text-primary-600 dark:text-primary-400">{{ $dateForecastTo }}</span>
                — satu kartu per menu
            </p>
        </div>
    </div>

    {{-- Satu card per menu --}}
    @foreach($predictions as $pred)
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 mb-4 overflow-hidden">

        {{-- Header menu --}}
        <div class="px-6 py-3 bg-blue-50 dark:bg-blue-900/20 border-b border-blue-100 dark:border-blue-800/40 flex flex-wrap items-center justify-between gap-2">
            <span class="font-bold text-sm text-blue-800 dark:text-blue-200">{{ $pred['nama_menu'] }}</span>
            <div class="flex items-center gap-3">
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    Total prediksi:
                    <span class="font-bold text-primary-600 dark:text-primary-400">
                        {{ number_format($pred['total_forecast'] ?? 0, 0) }} unit
                    </span>
                </span>
                <span class="text-xs text-gray-400">|</span>
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    MAE: <span class="font-semibold text-gray-700 dark:text-gray-300">{{ number_format($pred['mae'] ?? 0, 2) }}</span>
                </span>
            </div>
        </div>

        {{-- Tabel forecast harian --}}
        @if(count($pred['forecast'] ?? []))
        <div class="overflow-x-auto">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background-color:#1e40af;">
                        <th style="padding:11px 20px; text-align:left;   font-size:0.72rem; font-weight:600; color:#ffffff; text-transform:uppercase; letter-spacing:0.06em;">Tanggal</th>
                        <th style="padding:11px 20px; text-align:left;   font-size:0.72rem; font-weight:600; color:#ffffff; text-transform:uppercase; letter-spacing:0.06em;">Hari</th>
                        <th style="padding:11px 20px; text-align:center; font-size:0.72rem; font-weight:600; color:#ffffff; text-transform:uppercase; letter-spacing:0.06em;">Tipe Hari</th>
                        <th style="padding:11px 20px; text-align:right;  font-size:0.72rem; font-weight:600; color:#ffffff; text-transform:uppercase; letter-spacing:0.06em;">Prediksi (unit)</th>
                        <th style="padding:11px 20px; text-align:right;  font-size:0.72rem; font-weight:600; color:#ffffff; text-transform:uppercase; letter-spacing:0.06em;">Batas Bawah</th>
                        <th style="padding:11px 20px; text-align:right;  font-size:0.72rem; font-weight:600; color:#ffffff; text-transform:uppercase; letter-spacing:0.06em;">Batas Atas</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pred['forecast'] as $i => $f)
                    <tr style="background-color:{{ $i % 2 === 0 ? '#ffffff' : '#f8fafc' }}; border-bottom:1px solid #e2e8f0;">
                        <td style="padding:11px 20px; font-size:0.875rem; color:#334155; font-family:monospace; font-weight:500;">{{ $f['tanggal'] ?? '-' }}</td>
                        <td style="padding:11px 20px; font-size:0.875rem; color:#334155; font-weight:500;">{{ $f['hari'] ?? '-' }}</td>
                        <td style="padding:11px 20px; text-align:center;">
                            @if(($f['day_type'] ?? '') === 'Weekend')
                                <span style="display:inline-block; background:#fef3c7; color:#92400e; padding:3px 12px; border-radius:9999px; font-size:0.72rem; font-weight:600;">Weekend</span>
                            @else
                                <span style="display:inline-block; background:#dbeafe; color:#1d4ed8; padding:3px 12px; border-radius:9999px; font-size:0.72rem; font-weight:600;">Weekday</span>
                            @endif
                        </td>
                        <td style="padding:11px 20px; text-align:right;">
                            <span style="display:inline-block; background:#e0e7ff; color:#3730a3; padding:3px 14px; border-radius:9999px; font-size:0.875rem; font-weight:700;">{{ $f['prediksi'] ?? 0 }}</span>
                        </td>
                        <td style="padding:11px 20px; text-align:right; font-size:0.875rem; color:#64748b;">{{ $f['batas_bawah'] ?? '-' }}</td>
                        <td style="padding:11px 20px; text-align:right; font-size:0.875rem; color:#64748b;">{{ $f['batas_atas'] ?? '-' }}</td>
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
    {{-- OUTPUT 3 — ANALISIS RATA-RATA PENJUALAN: WEEKDAY VS WEEKEND    --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    @if($chartFeatureImportance)
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 mb-6 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-base font-bold text-gray-900 dark:text-white">Output 3 — Analisis Rata-rata Penjualan: Weekday vs Weekend</h2>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                Perbandingan rata-rata jumlah penjualan setiap menu pada hari kerja (weekday) dan akhir pekan (weekend)
                untuk melihat seberapa besar pengaruh tipe hari terhadap penjualan masing-masing menu.
            </p>
        </div>
        <div class="p-5">
            <img src="data:image/png;base64,{{ $chartFeatureImportance }}"
                 alt="Analisis Weekday vs Weekend" class="w-full rounded-lg"/>
        </div>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- OUTPUT 4 — VISUALISASI PREDIKSI vs AKTUAL PER ITEM             --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    @if($chartAllItems)
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 mb-6 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-base font-bold text-gray-900 dark:text-white">Output 4 — Visualisasi Prediksi vs Aktual per Item</h2>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                <span class="inline-block w-3 h-1 bg-gray-400 rounded mr-1"></span>Abu = data training &nbsp;·&nbsp;
                <span class="inline-block w-3 h-1 bg-blue-500 rounded mr-1"></span>Biru = aktual test &nbsp;·&nbsp;
                <span class="inline-block w-3 h-1 bg-red-400 rounded mr-1" style="border-top:2px dashed #f87171;"></span>Merah dashed = prediksi &nbsp;·&nbsp;
                Area merah = interval kepercayaan 95% &nbsp;·&nbsp;
                Kuning = Weekend
            </p>
        </div>
        <div class="p-5">
            <img src="data:image/png;base64,{{ $chartAllItems }}"
                 alt="Prediksi vs Aktual Semua Menu" class="w-full rounded-lg"/>
        </div>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- PROSES 1 — LOG PREPROCESSING (dipindah ke bawah)               --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    @if(count($preprocessLogs))
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 mb-6 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-base font-bold text-gray-900 dark:text-white">Proses 1 — Informasi Data &amp; Preprocessing</h2>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Tahapan pemrosesan data sebelum model dilatih</p>
        </div>
        <div class="divide-y divide-gray-50 dark:divide-gray-700/30">
            @foreach($preprocessLogs as $i => $log)
            <div class="flex items-start gap-4 px-6 py-4">
                <span class="shrink-0 w-6 h-6 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300 text-xs font-bold flex items-center justify-center mt-0.5">
                    {{ $i + 1 }}
                </span>
                <div>
                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $log['tahap'] ?? '' }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $log['detail'] ?? '' }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

@endif {{-- end hasResult --}}

</x-filament-panels::page>
