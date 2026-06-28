<x-filament-panels::page>

{{-- ── Error ────────────────────────────────────────────────────────────── --}}
@if($errorMsg)
    <div class="rounded-lg border border-yellow-200 dark:border-yellow-800 bg-yellow-50 dark:bg-yellow-900/20 p-4 mb-6">
        <p class="text-sm font-semibold text-yellow-700 dark:text-yellow-300">{{ $errorMsg }}</p>
        <p class="text-xs text-yellow-600 dark:text-yellow-400 mt-1">
            Buka halaman <strong>Klasterisasi Menu Penjualan</strong>,
            isi rentang tanggal, tekan <strong>"Jalankan Clustering"</strong>,
            lalu tekan <strong>"Perbarui Data Klasterisasi Menu"</strong> di kanan atas.
        </p>
    </div>
@endif

{{-- ── Belum ada data ───────────────────────────────────────────────────── --}}
@if(! $hasResult)
    <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-10 text-center">
        <p class="text-base font-semibold text-gray-700 dark:text-gray-200">Belum Ada Data Klasterisasi</p>
        <p class="text-sm text-gray-400 mt-2 max-w-md mx-auto">
            Jalankan proses di halaman
            <span class="font-semibold">Klasterisasi Menu Penjualan</span>
            terlebih dahulu, lalu tekan
            <span class="font-semibold">"Perbarui Data Klasterisasi Menu"</span> di kanan atas.
        </p>
    </div>

@else

{{-- ════════════════════════════════════════════════════════════════════════ --}}
{{-- DAFTAR 3 HASIL CLUSTERING TERBARU                                        --}}
{{-- ════════════════════════════════════════════════════════════════════════ --}}

@php
    $badgeStyle = [
        'Sangat Laris' => 'background-color:#dcfce7; color:#15803d;',
        'Laris'        => 'background-color:#dbeafe; color:#1d4ed8;',
        'Cukup'        => 'background-color:#fef9c3; color:#b45309;',
        'Kurang Laris' => 'background-color:#fee2e2; color:#dc2626;',
    ];
    $resultLabels = ['Terbaru', 'Kedua', 'Ketiga'];
@endphp

@foreach($results as $idx => $result)
@php
    $tableRows      = $result['table_rows']        ?? [];
    $kategoriRows   = $result['kategorisasi_rows'] ?? [];
    $clusterSummary = $result['cluster_summary']   ?? [];
    $bestK          = $result['best_k']            ?? 0;
    $silScore       = $result['silhouette_score']  ?? 0.0;
    $totalMenu      = $result['total_menu']        ?? 0;
    $lastRunAt      = $result['last_run_at']       ?? '-';
    $inputFrom      = $result['input_date_from']   ?? '-';
    $inputTo        = $result['input_date_to']     ?? '-';
    $dateFrom       = $result['date_range']['from'] ?? $inputFrom;
    $dateTo         = $result['date_range']['to']   ?? $inputTo;
    $chartBarJumlah     = $result['charts']['bar_jumlah']     ?? null;
    $chartBarKeuntungan = $result['charts']['bar_keuntungan'] ?? null;
    $chartKategorisasi  = $result['charts']['kategorisasi']   ?? null;
@endphp

<div class="mb-12">

    {{-- ── Header hasil ke-N ─────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-start gap-3 mb-5">
        @php
            $labelColors = ['bg-blue-600','bg-indigo-600','bg-violet-600'];
            $labelBg = $labelColors[$idx] ?? 'bg-gray-600';
        @endphp
        <span class="inline-flex items-center gap-1.5 rounded-full {{ $labelBg }} text-white text-xs font-bold px-3 py-1.5">
            {{ $idx === 0 ? 'Terbaru' : 'Hasil ke-' . $idx + 1 }}
        </span>
        <div class="flex flex-wrap gap-2 items-center">
            <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-xs px-3 py-1">
                Dijalankan: <strong class="ml-1">{{ $lastRunAt }}</strong>
            </span>
            <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-xs px-3 py-1">
                Input: <strong class="ml-1">{{ $inputFrom }}</strong> s/d <strong>{{ $inputTo }}</strong>
            </span>
            <span class="inline-flex items-center gap-1 rounded-full bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-300 text-xs px-3 py-1">
                Data aktual: <strong class="ml-1">{{ $dateFrom }}</strong> s/d <strong>{{ $dateTo }}</strong>
            </span>
            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 text-xs font-semibold px-3 py-1">
                K={{ $bestK }} · Sil={{ number_format($silScore, 3) }} · {{ $totalMenu }} menu
            </span>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{-- LAPORAN HASIL CLUSTERING MENU CAFE                              --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    @if(count($tableRows))
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 mb-6 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60">
            <h2 class="text-sm font-bold text-gray-800 dark:text-gray-100 uppercase tracking-wide">
                LAPORAN HASIL CLUSTERING MENU CAFE
            </h2>
            <p class="text-xs text-gray-400 mt-1">Periode: {{ $dateFrom }} s/d {{ $dateTo }}</p>
        </div>
        <div class="overflow-x-auto">
            <table style="width:100%; border-collapse:collapse; font-size:0.82rem;">
                <thead>
                    <tr style="background-color:#1d4ed8;">
                        <th style="padding:10px 14px; text-align:right; font-size:0.72rem; font-weight:700; color:#fff; text-transform:uppercase; letter-spacing:0.05em; border:1px solid #3b82f6; width:3rem;">No</th>
                        <th style="padding:10px 14px; text-align:left;  font-size:0.72rem; font-weight:700; color:#fff; text-transform:uppercase; letter-spacing:0.05em; border:1px solid #3b82f6;">Nama Item</th>
                        <th style="padding:10px 14px; text-align:right; font-size:0.72rem; font-weight:700; color:#fff; text-transform:uppercase; letter-spacing:0.05em; border:1px solid #3b82f6;">Total Jumlah</th>
                        <th style="padding:10px 14px; text-align:right; font-size:0.72rem; font-weight:700; color:#fff; text-transform:uppercase; letter-spacing:0.05em; border:1px solid #3b82f6;">Total Keuntungan</th>
                        <th style="padding:10px 14px; text-align:center; font-size:0.72rem; font-weight:700; color:#fff; text-transform:uppercase; letter-spacing:0.05em; border:1px solid #3b82f6;">Klaster</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tableRows as $ri => $row)
                        <tr style="{{ $ri % 2 === 0 ? 'background-color:#f8fafc;' : 'background-color:#ffffff;' }}">
                            <td style="padding:9px 14px; text-align:right; border:1px solid #e2e8f0; color:#94a3b8; font-size:0.75rem;">{{ $ri + 1 }}</td>
                            <td style="padding:9px 14px; border:1px solid #e2e8f0; color:#1e293b; font-weight:500;">{{ $row['Nama Item'] }}</td>
                            <td style="padding:9px 14px; text-align:right; border:1px solid #e2e8f0; color:#334155; font-family:monospace;">{{ number_format($row['Total_Jumlah'], 1) }}</td>
                            <td style="padding:9px 14px; text-align:right; border:1px solid #e2e8f0; color:#334155; font-family:monospace;">Rp {{ number_format($row['Total_Keuntungan'], 0, ',', '.') }}</td>
                            <td style="padding:9px 14px; text-align:center; border:1px solid #e2e8f0;">
                                <span style="display:inline-flex; align-items:center; justify-content:center; width:2rem; height:2rem; border-radius:9999px; background-color:#eff6ff; color:#1d4ed8; font-size:0.75rem; font-weight:700;">
                                    {{ $row['Klaster'] }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-6 py-3 bg-gray-50 dark:bg-gray-800/60 border-t border-gray-100 dark:border-gray-700">
            <p class="text-xs text-gray-500 dark:text-gray-400">
                Pengelompokan menu berdasarkan total jumlah penjualan dan total keuntungan menggunakan K-Means.
                Diurutkan per klaster, lalu total keuntungan tertinggi.
            </p>
        </div>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{-- RATA-RATA PENJUALAN DAN KEUNTUNGAN TIAP KLASTER                 --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    @if(count($clusterSummary))
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 mb-6 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60">
            <h2 class="text-sm font-bold text-gray-800 dark:text-gray-100 uppercase tracking-wide">
                RATA-RATA PENJUALAN DAN KEUNTUNGAN TIAP KLASTER
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table style="width:100%; border-collapse:collapse; font-size:0.82rem;">
                <thead>
                    <tr style="background-color:#1d4ed8;">
                        <th style="padding:10px 14px; text-align:center; font-size:0.72rem; font-weight:700; color:#fff; text-transform:uppercase; letter-spacing:0.05em; border:1px solid #3b82f6;">Klaster</th>
                        <th style="padding:10px 14px; text-align:right;  font-size:0.72rem; font-weight:700; color:#fff; text-transform:uppercase; letter-spacing:0.05em; border:1px solid #3b82f6;">Rata-rata Jumlah Penjualan</th>
                        <th style="padding:10px 14px; text-align:right;  font-size:0.72rem; font-weight:700; color:#fff; text-transform:uppercase; letter-spacing:0.05em; border:1px solid #3b82f6;">Rata-rata Keuntungan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($clusterSummary as $csi => $cs)
                        <tr style="{{ $csi % 2 === 0 ? 'background-color:#f8fafc;' : 'background-color:#ffffff;' }}">
                            <td style="padding:9px 14px; text-align:center; border:1px solid #e2e8f0;">
                                <span style="display:inline-flex; align-items:center; justify-content:center; width:2rem; height:2rem; border-radius:9999px; background-color:#eff6ff; color:#1d4ed8; font-size:0.75rem; font-weight:700;">
                                    {{ $cs['Klaster'] }}
                                </span>
                            </td>
                            <td style="padding:9px 14px; text-align:right; border:1px solid #e2e8f0; color:#334155; font-family:monospace;">
                                {{ number_format($cs['Rata-rata Jumlah Penjualan'], 2) }}
                            </td>
                            <td style="padding:9px 14px; text-align:right; border:1px solid #e2e8f0; color:#334155; font-family:monospace;">
                                Rp {{ number_format($cs['Rata-rata Keuntungan'], 0, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- ── Grafik Rata-rata Jumlah Penjualan per Klaster ──────────── --}}
    @if($chartBarJumlah)
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 mb-4 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60">
            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                Rata-rata Jumlah Penjualan Menu pada Setiap Klaster
            </h2>
        </div>
        <div class="p-4">
            <img src="data:image/png;base64,{{ $chartBarJumlah }}" alt="Chart Rata-rata Jumlah" class="w-full rounded"/>
        </div>
        <div class="px-6 pb-4 text-xs text-gray-500 dark:text-gray-400">
            Klaster dengan batang tertinggi menunjukkan kelompok menu dengan volume penjualan paling tinggi (paling diminati pelanggan).
        </div>
    </div>
    @endif

    {{-- ── Grafik Rata-rata Keuntungan per Klaster ────────────────── --}}
    @if($chartBarKeuntungan)
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 mb-6 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60">
            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                Rata-rata Keuntungan Menu pada Setiap Klaster
            </h2>
        </div>
        <div class="p-4">
            <img src="data:image/png;base64,{{ $chartBarKeuntungan }}" alt="Chart Rata-rata Keuntungan" class="w-full rounded"/>
        </div>
        <div class="px-6 pb-4 text-xs text-gray-500 dark:text-gray-400">
            Klaster dengan nilai rata-rata keuntungan tertinggi memberikan kontribusi keuntungan terbesar bagi cafe.
        </div>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{-- LAPORAN KATEGORISASI PENJUALAN MENU CAFE                        --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    @if(count($kategoriRows))
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 mb-4 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60">
            <h2 class="text-sm font-bold text-gray-800 dark:text-gray-100 uppercase tracking-wide">
                LAPORAN KATEGORISASI PENJUALAN MENU CAFE
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table style="width:100%; border-collapse:collapse; font-size:0.82rem;">
                <thead>
                    <tr style="background-color:#1d4ed8;">
                        <th style="padding:10px 14px; text-align:right;  font-size:0.72rem; font-weight:700; color:#fff; text-transform:uppercase; letter-spacing:0.05em; border:1px solid #3b82f6; width:3rem;">No</th>
                        <th style="padding:10px 14px; text-align:left;   font-size:0.72rem; font-weight:700; color:#fff; text-transform:uppercase; letter-spacing:0.05em; border:1px solid #3b82f6;">Nama Item</th>
                        <th style="padding:10px 14px; text-align:right;  font-size:0.72rem; font-weight:700; color:#fff; text-transform:uppercase; letter-spacing:0.05em; border:1px solid #3b82f6;">Total Jumlah</th>
                        <th style="padding:10px 14px; text-align:right;  font-size:0.72rem; font-weight:700; color:#fff; text-transform:uppercase; letter-spacing:0.05em; border:1px solid #3b82f6;">Total Keuntungan</th>
                        <th style="padding:10px 14px; text-align:center; font-size:0.72rem; font-weight:700; color:#fff; text-transform:uppercase; letter-spacing:0.05em; border:1px solid #3b82f6;">Klaster</th>
                        <th style="padding:10px 14px; text-align:left;   font-size:0.72rem; font-weight:700; color:#fff; text-transform:uppercase; letter-spacing:0.05em; border:1px solid #3b82f6;">Kategori</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($kategoriRows as $ri => $row)
                        <tr style="{{ $ri % 2 === 0 ? 'background-color:#f8fafc;' : 'background-color:#ffffff;' }}">
                            <td style="padding:9px 14px; text-align:right; border:1px solid #e2e8f0; color:#94a3b8; font-size:0.75rem;">{{ $ri + 1 }}</td>
                            <td style="padding:9px 14px; border:1px solid #e2e8f0; color:#1e293b; font-weight:500;">{{ $row['Nama Item'] }}</td>
                            <td style="padding:9px 14px; text-align:right; border:1px solid #e2e8f0; color:#334155; font-family:monospace;">{{ number_format($row['Total_Jumlah'], 1) }}</td>
                            <td style="padding:9px 14px; text-align:right; border:1px solid #e2e8f0; color:#334155; font-family:monospace;">Rp {{ number_format($row['Total_Keuntungan'], 0, ',', '.') }}</td>
                            <td style="padding:9px 14px; text-align:center; border:1px solid #e2e8f0;">
                                <span style="display:inline-flex; align-items:center; justify-content:center; width:2rem; height:2rem; border-radius:9999px; background-color:#eff6ff; color:#1d4ed8; font-size:0.75rem; font-weight:700;">
                                    {{ $row['Klaster'] }}
                                </span>
                            </td>
                            <td style="padding:9px 14px; border:1px solid #e2e8f0;">
                                <span style="display:inline-block; padding:2px 10px; border-radius:9999px; font-size:0.7rem; font-weight:600; {{ $badgeStyle[$row['Kategori']] ?? '' }}">
                                    {{ $row['Kategori'] }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800/60 border-t border-gray-100 dark:border-gray-700">
            <p class="text-xs font-semibold text-gray-600 dark:text-gray-300 mb-2">Keterangan Kategori Penjualan</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                Kategori penjualan ditentukan berdasarkan total jumlah penjualan setiap menu selama periode pengamatan.
            </p>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs">
                <div class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-full bg-emerald-500 shrink-0"></span>
                    <span class="text-gray-600 dark:text-gray-400"><strong>Sangat Laris</strong> ≥ 400</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-full bg-blue-500 shrink-0"></span>
                    <span class="text-gray-600 dark:text-gray-400"><strong>Laris</strong> 350–399</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-full bg-amber-500 shrink-0"></span>
                    <span class="text-gray-600 dark:text-gray-400"><strong>Cukup</strong> 200–349</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-full bg-red-500 shrink-0"></span>
                    <span class="text-gray-600 dark:text-gray-400"><strong>Kurang Laris</strong> &lt; 200</span>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Grafik Kategorisasi Penjualan ───────────────────────────── --}}
    @if($chartKategorisasi)
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60">
            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                Visualisasi Kategorisasi Penjualan Menu Cafe
            </h2>
        </div>
        <div class="p-4">
            <img src="data:image/png;base64,{{ $chartKategorisasi }}" alt="Kategorisasi Chart" class="w-full rounded"/>
        </div>
        <div class="px-6 pb-4 text-xs text-gray-500 dark:text-gray-400">
            Warna batang membedakan kategori Sangat Laris, Laris, Cukup, dan Kurang Laris.
            Semakin tinggi batang, semakin tinggi jumlah penjualan menu tersebut selama periode pengamatan.
        </div>
    </div>
    @endif

</div>{{-- end result card --}}

{{-- Pembatas antar hasil (kecuali setelah yang terakhir) --}}
@if(! $loop->last)
    <hr class="border-t-2 border-dashed border-gray-200 dark:border-gray-700 my-10"/>
@endif

@endforeach

@endif {{-- end hasResult --}}

</x-filament-panels::page>
