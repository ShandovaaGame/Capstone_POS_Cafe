<x-filament-panels::page>

{{-- ══════════════════════════════════════════════════════════════════════ --}}
{{-- INPUT RENTANG TANGGAL                                                   --}}
{{-- ══════════════════════════════════════════════════════════════════════ --}}
<div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 mb-6 shadow-sm overflow-hidden">

    {{-- Header kartu --}}
    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60">
        <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">Rentang Tanggal Data Penjualan</p>
        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Pilih periode data transaksi yang akan dianalisis (minimal 3 bulan)</p>
    </div>

    {{-- Input tanggal --}}
    <div class="px-6 py-5">
        <div style="border: 2px solid #3b82f6; border-radius: 10px; padding: 16px 20px; background: rgba(59,130,246,0.04);">
            <div class="flex flex-col sm:flex-row items-stretch sm:items-end gap-3">

                {{-- Dari Tanggal --}}
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

                {{-- Pemisah --}}
                <div class="hidden sm:flex items-center justify-center pb-0.5">
                    <span style="color:#3b82f6; font-size:0.875rem; font-weight:700;">s/d</span>
                </div>

                {{-- Sampai Tanggal --}}
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

        {{-- Status validasi --}}
        <div class="mt-4">
            @if(! $this->isDatesValid())
                <div class="rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 px-4 py-3">
                    <p class="text-xs text-amber-700 dark:text-amber-300">
                        @if(empty($inputDateFrom) && empty($inputDateTo))
                            Pilih <strong>Dari Tanggal</strong> dan <strong>Sampai Tanggal</strong> dengan rentang minimal <strong>3 bulan</strong>, lalu tekan <strong>"Jalankan Clustering"</strong> di kanan atas.
                        @elseif(empty($inputDateFrom) || empty($inputDateTo))
                            Lengkapi kedua tanggal — <strong>Dari Tanggal</strong> dan <strong>Sampai Tanggal</strong> harus diisi.
                        @else
                            Rentang tanggal terlalu pendek. Pilih periode minimal <strong>3 bulan</strong>.
                        @endif
                    </p>
                </div>
            @else
                <div class="rounded-lg bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 px-4 py-3">
                    <p class="text-xs font-medium text-emerald-700 dark:text-emerald-300">
                        Rentang tanggal valid. Tekan <strong>"Jalankan Clustering"</strong> di kanan atas untuk memulai analisis.
                    </p>
                </div>
            @endif
        </div>
    </div>

</div>

{{-- ══════════════════════════════════════════════════════════════════════ --}}
{{-- PENETAPAN BATAS KATEGORISASI PENJUALAN                                  --}}
{{-- ══════════════════════════════════════════════════════════════════════ --}}
@php
    $larisMax       = $sangat_laris_batas;
    $cukupMax       = $laris_batas_bawah - 1;
    $kurangLarisBatas = $cukup_batas_bawah;
@endphp
<div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 mb-6 shadow-sm overflow-hidden">

    {{-- Header kartu --}}
    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60">
        <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">Penetapan Batas Kategorisasi Penjualan</p>
        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Tetapkan batas jumlah penjualan untuk setiap kategori sebelum menjalankan analisis</p>
    </div>

    <div class="px-6 py-5">
        <div style="border: 2px solid #3b82f6; border-radius: 10px; padding: 20px; background: rgba(59,130,246,0.04);">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                {{-- Sangat Laris --}}
                <div class="rounded-lg border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-900/20 p-4">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="w-3 h-3 rounded-full bg-emerald-500 shrink-0"></span>
                        <span style="font-size:0.72rem; font-weight:700; color:#059669; text-transform:uppercase; letter-spacing:0.06em;">Sangat Laris</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">Lebih dari</span>
                        <input type="number"
                               wire:model.live="sangat_laris_batas"
                               min="1"
                               class="w-24 rounded-lg border border-gray-300 dark:border-gray-600
                                      bg-white dark:bg-gray-900 text-gray-900 dark:text-white
                                      px-3 py-1.5 text-sm shadow-sm text-center
                                      focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition" />
                        <span class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">penjualan</span>
                    </div>
                    <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-2 font-medium">
                        → Menu dengan total &gt; {{ $sangat_laris_batas }} penjualan
                    </p>
                </div>

                {{-- Laris --}}
                <div class="rounded-lg border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-900/20 p-4">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="w-3 h-3 rounded-full bg-blue-500 shrink-0"></span>
                        <span style="font-size:0.72rem; font-weight:700; color:#1d4ed8; text-transform:uppercase; letter-spacing:0.06em;">Laris</span>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">Antara</span>
                        <input type="number"
                               wire:model.live="laris_batas_bawah"
                               min="1"
                               class="w-24 rounded-lg border border-gray-300 dark:border-gray-600
                                      bg-white dark:bg-gray-900 text-gray-900 dark:text-white
                                      px-3 py-1.5 text-sm shadow-sm text-center
                                      focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition" />
                        <span class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">sampai</span>
                        <span class="w-24 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800
                                     px-3 py-1.5 text-sm text-center text-gray-500 dark:text-gray-400 select-none">
                            {{ $larisMax }}
                        </span>
                        <span class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">penjualan</span>
                    </div>
                    <p class="text-xs text-blue-600 dark:text-blue-400 mt-2 font-medium">
                        → Menu dengan total {{ $laris_batas_bawah }} – {{ $larisMax }} penjualan
                    </p>
                </div>

                {{-- Cukup --}}
                <div class="rounded-lg border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-4">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="w-3 h-3 rounded-full bg-amber-500 shrink-0"></span>
                        <span style="font-size:0.72rem; font-weight:700; color:#b45309; text-transform:uppercase; letter-spacing:0.06em;">Cukup</span>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">Antara</span>
                        <input type="number"
                               wire:model.live="cukup_batas_bawah"
                               min="1"
                               class="w-24 rounded-lg border border-gray-300 dark:border-gray-600
                                      bg-white dark:bg-gray-900 text-gray-900 dark:text-white
                                      px-3 py-1.5 text-sm shadow-sm text-center
                                      focus:border-amber-500 focus:ring-1 focus:ring-amber-500 transition" />
                        <span class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">sampai</span>
                        <span class="w-24 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800
                                     px-3 py-1.5 text-sm text-center text-gray-500 dark:text-gray-400 select-none">
                            {{ $cukupMax }}
                        </span>
                        <span class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">penjualan</span>
                    </div>
                    <p class="text-xs text-amber-600 dark:text-amber-400 mt-2 font-medium">
                        → Menu dengan total {{ $cukup_batas_bawah }} – {{ $cukupMax }} penjualan
                    </p>
                </div>

                {{-- Kurang Laris --}}
                <div class="rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 p-4">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="w-3 h-3 rounded-full bg-red-500 shrink-0"></span>
                        <span style="font-size:0.72rem; font-weight:700; color:#dc2626; text-transform:uppercase; letter-spacing:0.06em;">Kurang Laris</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">Kurang dari</span>
                        <span class="w-24 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800
                                     px-3 py-1.5 text-sm text-center text-gray-500 dark:text-gray-400 select-none">
                            {{ $kurangLarisBatas }}
                        </span>
                        <span class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">penjualan</span>
                    </div>
                    <p class="text-xs text-red-600 dark:text-red-400 mt-2 font-medium">
                        → Menu dengan total &lt; {{ $kurangLarisBatas }} penjualan
                    </p>
                </div>

            </div>
        </div>

        {{-- Status validasi kategorisasi --}}
        <div class="mt-4">
            @if(! $this->isCategoryValid())
                <div class="rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 px-4 py-3">
                    <p class="text-xs text-amber-700 dark:text-amber-300">
                        Batas kategorisasi tidak valid. Pastikan: <strong>Sangat Laris</strong> &gt; <strong>Batas Bawah Laris</strong> &gt; <strong>Batas Bawah Cukup</strong> &gt; 0.
                    </p>
                </div>
            @else
                <div class="rounded-lg bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 px-4 py-3">
                    <p class="text-xs font-medium text-emerald-700 dark:text-emerald-300">
                        Batas kategorisasi valid. Sangat Laris (&gt;{{ $sangat_laris_batas }}) · Laris ({{ $laris_batas_bawah }}–{{ $larisMax }}) · Cukup ({{ $cukup_batas_bawah }}–{{ $cukupMax }}) · Kurang Laris (&lt;{{ $kurangLarisBatas }})
                    </p>
                </div>
            @endif
        </div>
    </div>

</div>

{{-- ── Error ─────────────────────────────────────────────────────────────── --}}
@if($errorMsg)
    <div class="rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 p-4 mb-6">
        <p class="text-sm font-semibold text-red-700 dark:text-red-300">Clustering gagal</p>
        <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $errorMsg }}</p>
        <p class="text-xs text-red-500 mt-2">
            Pastikan FastAPI sudah berjalan:
            <code class="bg-red-100 dark:bg-red-900 px-1.5 py-0.5 rounded font-mono">
                cd datamining &amp;&amp; uvicorn api:app --port 8001
            </code>
        </p>
    </div>
@endif

{{-- ── Belum ada hasil ─────────────────────────────────────────────────────── --}}
@if(! $hasResult && ! $errorMsg)
    <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-10 text-center">
        <p class="text-base font-semibold text-gray-700 dark:text-gray-200">Belum Ada Hasil Clustering</p>
        <p class="text-sm text-gray-400 mt-2">
            Isi rentang tanggal di atas, lalu tekan
            <span class="font-semibold text-primary-500">"Jalankan Clustering"</span>
            di kanan atas untuk memulai analisis K-Means.
        </p>
        <div class="mt-5 inline-block text-left bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 p-4 text-xs font-mono text-gray-500 dark:text-gray-400">
            <p class="mb-1">cd datamining</p>
            <p>uvicorn api:app --port 8001 --reload</p>
        </div>
    </div>
@endif

{{-- ════════════════════════════════════════════════════════════════════════ --}}
{{-- HASIL CLUSTERING                                                          --}}
{{-- ════════════════════════════════════════════════════════════════════════ --}}
@if($hasResult)

    {{-- Info header --}}
    <div class="rounded-lg border border-blue-100 dark:border-blue-800 bg-blue-50 dark:bg-blue-900/20 px-5 py-3 mb-6 flex flex-wrap gap-4 items-center justify-between text-xs">
        <div class="flex items-center gap-2 text-blue-700 dark:text-blue-300">
            <span>Dijalankan pada: <strong>{{ $lastRunAt }}</strong></span>
        </div>
        <div class="flex items-center gap-2 text-blue-700 dark:text-blue-300">
            <span>Data penjualan: <strong>{{ $usedDateFrom }}</strong> s/d <strong>{{ $usedDateTo }}</strong></span>
        </div>
        <div class="flex items-center gap-2 text-blue-700 dark:text-blue-300">
            <span>Rentang aktual: <strong>{{ $dateFrom }}</strong> s/d <strong>{{ $dateTo }}</strong></span>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- LAPORAN HASIL CLUSTERING MENU CAFE                                   --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    @if(count($tableRows))
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 mb-8 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60">
            <h2 class="text-sm font-bold text-gray-800 dark:text-gray-100 uppercase tracking-wide">
                LAPORAN HASIL CLUSTERING MENU CAFE
            </h2>
            <p class="text-xs text-gray-400 mt-1">Data penjualan: {{ $usedDateFrom }} s/d {{ $usedDateTo }} &nbsp;·&nbsp; Data aktual: {{ $dateFrom }} s/d {{ $dateTo }}</p>
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
                    @foreach($tableRows as $i => $row)
                        <tr style="{{ $i % 2 === 0 ? 'background-color:#f8fafc;' : 'background-color:#ffffff;' }}">
                            <td style="padding:9px 14px; text-align:right; border:1px solid #e2e8f0; color:#94a3b8; font-size:0.75rem;">{{ $i + 1 }}</td>
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
        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800/60 border-t border-gray-100 dark:border-gray-700">
            <p class="text-xs text-gray-500 dark:text-gray-400">
                <strong>Keterangan:</strong> Tabel menunjukkan hasil pengelompokan menu berdasarkan total jumlah penjualan
                dan total keuntungan menggunakan algoritma K-Means. Diurutkan per klaster, lalu total keuntungan tertinggi.
            </p>
        </div>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- RATA-RATA PENJUALAN DAN KEUNTUNGAN TIAP KLASTER                      --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    @if(count($clusterSummary))
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 mb-8 shadow-sm overflow-hidden">
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


    {{-- ── Grafik Rata-rata Jumlah Penjualan per Klaster ───────────────── --}}
    @if($chartBarJumlah)
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 mb-6 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60">
            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                Rata-rata Jumlah Penjualan Menu pada Setiap Klaster
            </h2>
        </div>
        <div class="p-4">
            <img src="data:image/png;base64,{{ $chartBarJumlah }}" alt="Bar Chart Jumlah" class="w-full rounded"/>
        </div>
        <div class="px-6 pb-4 text-xs text-gray-500 dark:text-gray-400">
            Grafik menunjukkan rata-rata jumlah penjualan menu pada setiap klaster.
            Klaster dengan batang tertinggi menunjukkan kelompok menu dengan volume penjualan paling tinggi (paling diminati pelanggan).
        </div>
    </div>
    @endif

    {{-- ── Grafik Rata-rata Keuntungan per Klaster ────────────────────── --}}
    @if($chartBarKeuntungan)
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 mb-8 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60">
            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                Rata-rata Keuntungan Menu pada Setiap Klaster
            </h2>
        </div>
        <div class="p-4">
            <img src="data:image/png;base64,{{ $chartBarKeuntungan }}" alt="Bar Chart Keuntungan" class="w-full rounded"/>
        </div>
        <div class="px-6 pb-4 text-xs text-gray-500 dark:text-gray-400">
            Grafik menunjukkan rata-rata keuntungan menu pada setiap klaster.
            Klaster dengan nilai rata-rata keuntungan tertinggi menunjukkan kelompok menu yang memberikan kontribusi keuntungan terbesar bagi cafe.
        </div>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- LAPORAN KATEGORISASI PENJUALAN MENU CAFE                             --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    @if(count($kategoriRows))
    @php
        $badgeStyle = [
            'Sangat Laris' => 'background-color:#dcfce7; color:#15803d;',
            'Laris'        => 'background-color:#dbeafe; color:#1d4ed8;',
            'Cukup'        => 'background-color:#fef9c3; color:#b45309;',
            'Kurang Laris' => 'background-color:#fee2e2; color:#dc2626;',
        ];
    @endphp
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 mb-6 shadow-sm overflow-hidden">
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
                    @foreach($kategoriRows as $i => $row)
                        <tr style="{{ $i % 2 === 0 ? 'background-color:#f8fafc;' : 'background-color:#ffffff;' }}">
                            <td style="padding:9px 14px; text-align:right; border:1px solid #e2e8f0; color:#94a3b8; font-size:0.75rem;">{{ $i + 1 }}</td>
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
                    <span class="text-gray-600 dark:text-gray-400"><strong>Sangat Laris</strong> &gt; {{ $sangat_laris_batas }}</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-full bg-blue-500 shrink-0"></span>
                    <span class="text-gray-600 dark:text-gray-400"><strong>Laris</strong> {{ $laris_batas_bawah }}–{{ $sangat_laris_batas }}</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-full bg-amber-500 shrink-0"></span>
                    <span class="text-gray-600 dark:text-gray-400"><strong>Cukup</strong> {{ $cukup_batas_bawah }}–{{ $laris_batas_bawah - 1 }}</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-full bg-red-500 shrink-0"></span>
                    <span class="text-gray-600 dark:text-gray-400"><strong>Kurang Laris</strong> &lt; {{ $cukup_batas_bawah }}</span>
                </div>
            </div>
        </div>
    </div>
    @endif


    {{-- ── Grafik Kategorisasi Penjualan ───────────────────────────────── --}}
    @if($chartKategorisasi)
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 mb-8 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60">
            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                Visualisasi Kategorisasi Penjualan Menu Cafe
            </h2>
        </div>
        <div class="p-4">
            <img src="data:image/png;base64,{{ $chartKategorisasi }}" alt="Kategorisasi Chart" class="w-full rounded"/>
        </div>
        <div class="px-6 pb-4 text-xs text-gray-500 dark:text-gray-400">
            Grafik menunjukkan total jumlah penjualan setiap menu berdasarkan kategori penjualannya.
            Warna batang membedakan kategori Sangat Laris, Laris, Cukup, dan Kurang Laris.
            Semakin tinggi batang, semakin tinggi jumlah penjualan menu tersebut selama periode pengamatan.
        </div>
    </div>
    @endif

    {{-- ── Elbow Method & Silhouette Score ────────────────────────────── --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        @if($chartElbow)
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Elbow Method</h2>
                <p class="text-xs text-gray-400 mt-0.5">Penentuan jumlah klaster optimal berdasarkan inertia</p>
            </div>
            <div class="p-4">
                <img src="data:image/png;base64,{{ $chartElbow }}" alt="Elbow Chart" class="w-full rounded"/>
            </div>
        </div>
        @endif
        @if($chartSilhouette)
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Silhouette Score</h2>
                <p class="text-xs text-gray-400 mt-0.5">Kualitas pengelompokan — semakin tinggi semakin baik</p>
            </div>
            <div class="p-4">
                <img src="data:image/png;base64,{{ $chartSilhouette }}" alt="Silhouette Chart" class="w-full rounded"/>
            </div>
        </div>
        @endif
    </div>

    {{-- ── Stat bar ──────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-3 gap-4 mb-8">
        @foreach([
            ['label' => 'K Optimal',        'value' => $bestK,
             'sub' => 'Jumlah klaster terbaik'],
            ['label' => 'Silhouette Score',  'value' => number_format($silhouetteScore, 3),
             'sub' => $silhouetteScore >= 0.7 ? 'Kualitas tinggi' : ($silhouetteScore >= 0.5 ? 'Kualitas sedang' : 'Kualitas rendah')],
            ['label' => 'Menu Dianalisis',   'value' => $totalMenu,
             'sub' => 'Dari data riwayat pesanan'],
        ] as $s)
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-6 py-5 shadow-sm">
                <p class="text-xs text-gray-400 uppercase tracking-widest mb-2">{{ $s['label'] }}</p>
                <p class="text-3xl font-bold text-gray-800 dark:text-white">{{ $s['value'] }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ $s['sub'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- ── Log Tahapan Analisis ─────────────────────────────────────────── --}}
    @if(count($preprocessLogs))
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 mb-8 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60">
            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                Tahapan Analisis Data
            </h2>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Keterangan proses yang terjadi pada modul Klasterisasi Menu Penjualan</p>
        </div>
        <div class="divide-y divide-gray-50 dark:divide-gray-700/40">
            @foreach($preprocessLogs as $i => $log)
                <div class="flex items-start gap-4 px-6 py-4">
                    <span class="shrink-0 w-6 h-6 rounded-full bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400 text-xs font-bold flex items-center justify-center mt-0.5">
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
