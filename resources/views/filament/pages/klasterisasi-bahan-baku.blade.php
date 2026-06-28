<x-filament-panels::page>

{{-- ══════════════════════════════════════════════════════════════════════ --}}
{{-- FORM INPUT RENTANG TANGGAL                                             --}}
{{-- ══════════════════════════════════════════════════════════════════════ --}}
<div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 mb-6">
    <h2 class="text-base font-bold text-gray-800 dark:text-white mb-1">
        Input Rentang Tanggal Data Penggunaan Bahan Baku
    </h2>
    <p class="text-xs text-gray-500 dark:text-gray-400 mb-5">
        Tentukan rentang tanggal data historis penggunaan bahan baku yang akan diolah oleh K-Means Clustering. Rentang tanggal minimal <span class="font-semibold text-primary-600">3 bulan</span>.
    </p>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
        <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                Tanggal Mulai
            </label>
            <input type="date"
                   wire:model.live="inputDateFrom"
                   class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-white text-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"/>
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                Tanggal Selesai
            </label>
            <input type="date"
                   wire:model.live="inputDateTo"
                   class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-white text-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"/>
        </div>
    </div>

    {{-- Status rentang tanggal --}}
    @if(! $inputDateFrom && ! $inputDateTo)
        <div class="rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 px-4 py-3">
            <p class="text-xs text-blue-700 dark:text-blue-300">
                Isi rentang tanggal data riwayat penggunaan bahan baku yang akan digunakan untuk diolah pada data mining klasterisasi penggunaan bahan baku. Rentang tanggal minimal <span class="font-semibold">3 bulan</span>.
            </p>
        </div>
    @elseif($dateRangeError)
        <div class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 px-4 py-3">
            <p class="text-xs text-red-700 dark:text-red-300">{{ $dateRangeError }}</p>
        </div>
    @elseif($this->isDateRangeValid())
        <div class="rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 px-4 py-3">
            <p class="text-xs text-green-700 dark:text-green-300">
                Rentang tanggal valid:
                <span class="font-semibold">{{ \Carbon\Carbon::parse($inputDateFrom)->translatedFormat('d M Y') }}</span>
                s/d
                <span class="font-semibold">{{ \Carbon\Carbon::parse($inputDateTo)->translatedFormat('d M Y') }}</span>
                ({{ \Carbon\Carbon::parse($inputDateFrom)->diffInMonths(\Carbon\Carbon::parse($inputDateTo)) }} bulan).
                Tekan tombol <span class="font-semibold">"Jalankan Clustering Bahan Baku"</span> di kanan atas.
            </p>
        </div>
    @endif
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
    {{-- LOG PREPROCESSING                                               --}}
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
                <span class="shrink-0 w-7 h-7 rounded-full bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 text-xs font-bold flex items-center justify-center mt-0.5">
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
    {{-- VISUALISASI 3 & 4: Elbow + Silhouette                         --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        @if($chartElbow)
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-base font-bold text-gray-900 dark:text-white">Elbow Method</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Penentuan jumlah klaster optimal berdasarkan inertia (SSE)</p>
            </div>
            <div class="p-4">
                <img src="data:image/png;base64,{{ $chartElbow }}" alt="Elbow Chart" class="w-full rounded"/>
            </div>
        </div>
        @endif
        @if($chartSilhouette)
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-base font-bold text-gray-900 dark:text-white">Silhouette Score per K</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Kualitas pengelompokan — semakin tinggi semakin baik</p>
            </div>
            <div class="p-4">
                <img src="data:image/png;base64,{{ $chartSilhouette }}" alt="Silhouette Chart" class="w-full rounded"/>
            </div>
        </div>
        @endif
    </div>

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- RINGKASAN PER KLASTER (summary cards)                          --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    @if(count($clusters))
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 mb-6 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-base font-bold text-gray-900 dark:text-white">Ringkasan Bahan Baku per Klaster</h2>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Daftar bahan baku yang masuk ke masing-masing klaster</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 p-5">
            @foreach($clusters as $cluster)
            <div class="rounded-lg border border-blue-200 dark:border-blue-700 bg-blue-50 dark:bg-blue-900/20 p-4">
                <div class="flex items-center justify-between mb-3">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-600 text-white text-sm font-bold">
                        {{ $cluster['klaster'] }}
                    </span>
                    <span class="text-xs text-gray-400 dark:text-gray-500">{{ $cluster['count'] }} bahan baku</span>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                    Rata-rata: <span class="font-semibold text-gray-700 dark:text-gray-200">{{ number_format($cluster['avg_usage'], 1) }}</span>
                    &nbsp;·&nbsp;
                    Total: <span class="font-semibold text-gray-700 dark:text-gray-200">{{ number_format($cluster['total_usage'], 1) }}</span>
                </p>
                <div class="flex flex-wrap gap-1.5">
                    @foreach($cluster['ingredients'] as $ing)
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300">
                            {{ $ing['name'] }}
                            @if($ing['unit'])
                                <span class="opacity-60">({{ $ing['unit'] }})</span>
                            @endif
                        </span>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

@endif {{-- end hasResult --}}

</x-filament-panels::page>
