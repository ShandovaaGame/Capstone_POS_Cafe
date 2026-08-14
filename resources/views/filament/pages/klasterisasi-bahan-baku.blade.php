<x-filament-panels::page>

{{-- ══════════════════════════════════════════════════════════════════════ --}}
{{-- INPUT RENTANG TANGGAL                                                   --}}
{{-- ══════════════════════════════════════════════════════════════════════ --}}
<div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 mb-6 shadow-sm overflow-hidden">

    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60">
        <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">Rentang Tanggal Data Penggunaan Bahan Baku</p>
        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Tentukan periode data historis penggunaan bahan baku untuk K-Means Clustering (minimal 3 bulan)</p>
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
                        Pilih <strong>Dari Tanggal</strong> dan <strong>Sampai Tanggal</strong> dengan rentang minimal <strong>3 bulan</strong>, lalu tekan <strong>"Jalankan Clustering Bahan Baku"</strong> di kanan atas.
                    </p>
                </div>
            @elseif($dateRangeError)
                <div class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-4 py-3">
                    <p class="text-xs text-red-700 dark:text-red-300">{{ $dateRangeError }}</p>
                </div>
            @elseif($this->isDateRangeValid())
                <div class="rounded-lg bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 px-4 py-3">
                    <p class="text-xs font-medium text-emerald-700 dark:text-emerald-300">
                        Rentang tanggal valid: <strong>{{ \Carbon\Carbon::parse($inputDateFrom)->translatedFormat('d M Y') }}</strong> s/d <strong>{{ \Carbon\Carbon::parse($inputDateTo)->translatedFormat('d M Y') }}</strong>
                        ({{ \Carbon\Carbon::parse($inputDateFrom)->diffInMonths(\Carbon\Carbon::parse($inputDateTo)) }} bulan).
                        Tekan <strong>"Jalankan Clustering Bahan Baku"</strong> di kanan atas.
                    </p>
                </div>
            @endif
        </div>
    </div>

</div>

{{-- ── Error dari FastAPI ──────────────────────────────────────────────── --}}
@if($errorMsg)
    <div class="rounded-xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 p-4 mb-6">
        <p class="text-sm font-semibold text-red-700 dark:text-red-300">Clustering gagal</p>
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
@if(! $hasResult)
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-10 text-center">
        <p class="text-base font-semibold text-gray-700 dark:text-gray-200">Belum Ada Hasil Clustering Bahan Baku</p>
        <p class="text-sm text-gray-400 mt-2 max-w-md mx-auto">
            Isi rentang tanggal data penggunaan bahan baku di atas, lalu tekan tombol
            <span class="font-semibold text-primary-600">"Jalankan Clustering Bahan Baku"</span>
            di kanan atas.
        </p>
    </div>

@else
{{-- ════════════════════════════════════════════════════════════════════════ --}}
{{-- HASIL CLUSTERING                                                         --}}
{{-- ════════════════════════════════════════════════════════════════════════ --}}

    {{-- Meta bar --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5 px-1">
        <div class="flex flex-wrap gap-x-5 gap-y-1 text-xs text-gray-400 dark:text-gray-500">
            <span>
                Dijalankan: <span class="font-medium text-gray-600 dark:text-gray-300">{{ $lastRunAt }}</span>
            </span>
            <span>
                Data yang digunakan:
                <span class="font-medium text-gray-600 dark:text-gray-300">
                    {{ \Carbon\Carbon::parse($inputDateFrom)->translatedFormat('d M Y') }}
                    s/d
                    {{ \Carbon\Carbon::parse($inputDateTo)->translatedFormat('d M Y') }}
                </span>
            </span>
            <span>
                Data aktual di DB:
                <span class="font-medium text-primary-600 dark:text-primary-400">{{ $dateFrom }} s/d {{ $dateTo }}</span>
            </span>
        </div>
    </div>

    {{-- Stat bar --}}
    <div class="grid grid-cols-3 gap-4 mb-8">
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-6 py-5">
            <p class="text-xs text-gray-400 uppercase tracking-widest mb-1">K Optimal</p>
            <p class="text-3xl font-bold text-gray-800 dark:text-white">{{ $bestK }}</p>
            <p class="text-xs text-gray-400 mt-1">Jumlah klaster terbaik</p>
        </div>
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-6 py-5">
            <p class="text-xs text-gray-400 uppercase tracking-widest mb-1">Silhouette Score</p>
            <p class="text-3xl font-bold text-gray-800 dark:text-white">{{ number_format($silhouetteScore, 3) }}</p>
            <p class="text-xs mt-1 {{ $silhouetteScore >= 0.7 ? 'text-green-500' : ($silhouetteScore >= 0.5 ? 'text-yellow-500' : 'text-red-400') }}">
                {{ $silhouetteScore >= 0.7 ? 'Kualitas tinggi' : ($silhouetteScore >= 0.5 ? 'Kualitas sedang' : 'Kualitas rendah') }}
            </p>
        </div>
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-6 py-5">
            <p class="text-xs text-gray-400 uppercase tracking-widest mb-1">Bahan Baku Dianalisis</p>
            <p class="text-3xl font-bold text-gray-800 dark:text-white">{{ $totalIngredients }}</p>
            <p class="text-xs text-gray-400 mt-1">Dari data pemakaian harian</p>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- TABEL KLASTERISASI BAHAN BAKU                                  --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    @if(count($tableRows))
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 mb-8 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-base font-bold text-gray-900 dark:text-white">Tabel Klasterisasi Bahan Baku Berdasarkan Jumlah Penggunaan</h2>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                Diurutkan berdasarkan klaster kemudian jumlah penggunaan tertinggi — data
                <span class="font-medium text-gray-700 dark:text-gray-300">{{ $dateFrom }} s/d {{ $dateTo }}</span>
            </p>
        </div>
        <div class="overflow-x-auto">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background-color:#1e40af;">
                        <th style="padding:12px 18px; text-align:right;  font-size:0.7rem; font-weight:600; color:#ffffff; text-transform:uppercase; letter-spacing:0.06em; white-space:nowrap; width:52px;">No</th>
                        <th style="padding:12px 18px; text-align:left;   font-size:0.7rem; font-weight:600; color:#ffffff; text-transform:uppercase; letter-spacing:0.06em; white-space:nowrap;">Nama Bahan Baku</th>
                        <th style="padding:12px 18px; text-align:center; font-size:0.7rem; font-weight:600; color:#ffffff; text-transform:uppercase; letter-spacing:0.06em; white-space:nowrap;">Satuan</th>
                        <th style="padding:12px 18px; text-align:right;  font-size:0.7rem; font-weight:600; color:#ffffff; text-transform:uppercase; letter-spacing:0.06em; white-space:nowrap;">Total Penggunaan</th>
                        <th style="padding:12px 18px; text-align:center; font-size:0.7rem; font-weight:600; color:#ffffff; text-transform:uppercase; letter-spacing:0.06em; white-space:nowrap;">Klaster</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tableRows as $i => $row)
                    <tr style="background-color:{{ $i % 2 === 0 ? '#ffffff' : '#f8fafc' }}; border-bottom:1px solid #e2e8f0;">
                        <td style="padding:11px 18px; text-align:right; font-size:0.8rem; color:#9ca3af;">{{ $i + 1 }}</td>
                        <td style="padding:11px 18px; text-align:left; font-size:0.875rem; font-weight:600; color:#1e40af;">{{ $row['Nama Bahan Baku'] }}</td>
                        <td style="padding:11px 18px; text-align:center;">
                            <span style="display:inline-block; background:#e0e7ff; color:#3730a3; padding:2px 10px; border-radius:9999px; font-size:0.75rem; font-weight:600;">
                                {{ $row['Satuan'] ?: '-' }}
                            </span>
                        </td>
                        <td style="padding:11px 18px; text-align:right; font-size:0.875rem; font-weight:700; color:#0f172a;">
                            {{ number_format($row['Total Penggunaan'], 2) }}
                        </td>
                        <td style="padding:11px 18px; text-align:center;">
                            <span style="display:inline-block; background:#dbeafe; color:#1d4ed8; padding:3px 12px; border-radius:9999px; font-size:0.8rem; font-weight:700;">
                                {{ $row['Klaster'] }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- TABEL RATA-RATA PER KLASTER                                    --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    @if(count($rataRataTable))
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 mb-8 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-base font-bold text-gray-900 dark:text-white">Tabel Rata-rata Jumlah Penggunaan Bahan Baku per Klaster</h2>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Diurutkan berdasarkan rata-rata jumlah penggunaan tertinggi</p>
        </div>
        <div class="overflow-x-auto mb-2">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background-color:#1e40af;">
                        <th style="padding:12px 18px; text-align:center; font-size:0.7rem; font-weight:600; color:#ffffff; text-transform:uppercase; letter-spacing:0.06em; white-space:nowrap;">Klaster</th>
                        <th style="padding:12px 18px; text-align:right;  font-size:0.7rem; font-weight:600; color:#ffffff; text-transform:uppercase; letter-spacing:0.06em; white-space:nowrap;">Rata-rata Jumlah Penggunaan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rataRataTable as $i => $row)
                    <tr style="background-color:{{ $i % 2 === 0 ? '#ffffff' : '#f8fafc' }}; border-bottom:1px solid #e2e8f0;">
                        <td style="padding:11px 18px; text-align:center;">
                            <span style="display:inline-block; background:#dbeafe; color:#1d4ed8; padding:3px 14px; border-radius:9999px; font-size:0.8rem; font-weight:700;">
                                Klaster {{ $row['Klaster'] }}
                            </span>
                        </td>
                        <td style="padding:11px 18px; text-align:right; font-size:0.875rem; font-weight:700; color:#0f172a;">
                            {{ number_format($row['Rata-rata Jumlah Penggunaan'], 2) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-6 pb-5">
            <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                <span class="font-semibold text-gray-700 dark:text-gray-300">Keterangan:</span>
                Klaster dengan nilai rata-rata jumlah penggunaan yang lebih tinggi menunjukkan kelompok bahan baku yang lebih sering digunakan dalam operasional kafe. Informasi ini dapat membantu pengelola dalam mengidentifikasi bahan baku yang memiliki pergerakan penggunaan paling aktif sehingga dapat dijadikan dasar dalam menentukan prioritas pengawasan stok, frekuensi pembelian ulang, dan perencanaan persediaan bahan baku.
            </p>
        </div>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- VISUALISASI 1: Rata-rata per Klaster                           --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    @if($chartRataKlaster)
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 mb-8 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-base font-bold text-gray-900 dark:text-white">Visualisasi Rata-rata Jumlah Penggunaan Bahan Baku per Klaster</h2>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Diagram batang rata-rata jumlah penggunaan tiap klaster hasil K-Means</p>
        </div>
        <div class="p-5">
            <img src="data:image/png;base64,{{ $chartRataKlaster }}"
                 alt="Rata-rata per Klaster" class="w-full rounded-lg"/>
        </div>
        <div class="px-6 pb-5">
            <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                <span class="font-semibold text-gray-700 dark:text-gray-300">Keterangan:</span>
                Semakin tinggi nilai rata-rata jumlah penggunaan bahan baku pada suatu klaster, maka bahan baku dalam klaster tersebut memiliki aktivitas penggunaan yang relatif lebih tinggi dibandingkan klaster lainnya. Informasi ini dapat digunakan sebagai dasar untuk menentukan prioritas pemantauan stok dan perencanaan pengadaan bahan baku.
            </p>
        </div>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- VISUALISASI 2: Jumlah per Bahan Baku (colored by Klaster)     --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    @if($chartBar)
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 mb-8 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-base font-bold text-gray-900 dark:text-white">Visualisasi Jumlah Penggunaan Bahan Baku Berdasarkan Hasil Klasterisasi</h2>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Setiap batang mewakili total penggunaan bahan baku, diwarnai sesuai klaster hasil K-Means</p>
        </div>
        <div class="p-5">
            <img src="data:image/png;base64,{{ $chartBar }}"
                 alt="Clustering Bar Chart" class="w-full rounded-lg"/>
        </div>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- VISUALISASI 3: Elbow Method                                     --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    @if($chartElbow)
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 mb-6 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-base font-bold text-gray-900 dark:text-white">Elbow Method</h2>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Penentuan jumlah klaster optimal berdasarkan inertia (SSE)</p>
        </div>
        <div class="p-5">
            <img src="data:image/png;base64,{{ $chartElbow }}" alt="Elbow Chart" class="w-full rounded"/>
        </div>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- VISUALISASI 4: Silhouette Score per K                           --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    @if($chartSilhouette)
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 mb-8 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-base font-bold text-gray-900 dark:text-white">Silhouette Score per K</h2>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Kualitas pengelompokan — semakin tinggi semakin baik</p>
        </div>
        <div class="p-5">
            <img src="data:image/png;base64,{{ $chartSilhouette }}" alt="Silhouette Chart" class="w-full rounded"/>
        </div>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- RINGKASAN PER KLASTER (tabel)                                   --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    @if(count($clusters))
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 mb-8 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-base font-bold text-gray-900 dark:text-white">Ringkasan Bahan Baku per Klaster</h2>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Daftar bahan baku yang masuk ke masing-masing klaster</p>
        </div>
        <div class="overflow-x-auto">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background-color:#1e40af;">
                        <th style="padding:12px 18px; text-align:center; font-size:0.7rem; font-weight:600; color:#ffffff; text-transform:uppercase; letter-spacing:0.06em; white-space:nowrap; width:80px;">Klaster</th>
                        <th style="padding:12px 18px; text-align:center; font-size:0.7rem; font-weight:600; color:#ffffff; text-transform:uppercase; letter-spacing:0.06em; white-space:nowrap;">Jml Bahan Baku</th>
                        <th style="padding:12px 18px; text-align:right;  font-size:0.7rem; font-weight:600; color:#ffffff; text-transform:uppercase; letter-spacing:0.06em; white-space:nowrap;">Rata-rata Penggunaan</th>
                        <th style="padding:12px 18px; text-align:right;  font-size:0.7rem; font-weight:600; color:#ffffff; text-transform:uppercase; letter-spacing:0.06em; white-space:nowrap;">Total Penggunaan</th>
                        <th style="padding:12px 18px; text-align:left;   font-size:0.7rem; font-weight:600; color:#ffffff; text-transform:uppercase; letter-spacing:0.06em;">Daftar Bahan Baku</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($clusters as $ci => $cluster)
                    <tr style="background-color:{{ $ci % 2 === 0 ? '#ffffff' : '#f8fafc' }}; border-bottom:1px solid #e2e8f0; vertical-align:top;">
                        <td style="padding:14px 18px; text-align:center;">
                            <span style="display:inline-flex; align-items:center; justify-content:center; width:36px; height:36px; border-radius:9999px; background:#1d4ed8; color:#ffffff; font-size:0.875rem; font-weight:700;">
                                {{ $cluster['klaster'] }}
                            </span>
                        </td>
                        <td style="padding:14px 18px; text-align:center; font-size:0.875rem; font-weight:700; color:#0f172a;">
                            {{ $cluster['count'] }}
                        </td>
                        <td style="padding:14px 18px; text-align:right; font-size:0.875rem; font-weight:700; color:#0f172a;">
                            {{ number_format($cluster['avg_usage'], 1) }}
                        </td>
                        <td style="padding:14px 18px; text-align:right; font-size:0.875rem; font-weight:700; color:#0f172a;">
                            {{ number_format($cluster['total_usage'], 1) }}
                        </td>
                        <td style="padding:14px 18px; text-align:left;">
                            <div style="display:flex; flex-wrap:wrap; gap:6px;">
                                @foreach($cluster['ingredients'] as $ing)
                                    <span style="display:inline-flex; align-items:center; gap:3px; padding:3px 10px; border-radius:9999px; background:#dbeafe; color:#1e40af; font-size:0.73rem; font-weight:600; white-space:nowrap;">
                                        {{ $ing['name'] }}
                                        @if($ing['unit'])
                                            <span style="opacity:0.65; font-weight:500;">({{ $ing['unit'] }})</span>
                                        @endif
                                    </span>
                                @endforeach
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- TAHAPAN PREPROCESSING DATA (dipindah ke bawah)                  --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    @if(count($preprocessLogs))
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 mb-8 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-base font-bold text-gray-900 dark:text-white">Tahapan Preprocessing Data</h2>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Proses pembersihan dan persiapan data sebelum clustering bahan baku</p>
        </div>
        <div class="divide-y divide-gray-50 dark:divide-gray-700/30">
            @foreach($preprocessLogs as $i => $log)
            <div class="flex items-start gap-4 px-6 py-4">
                <span class="shrink-0 w-7 h-7 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300 text-xs font-bold flex items-center justify-center mt-0.5">
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
