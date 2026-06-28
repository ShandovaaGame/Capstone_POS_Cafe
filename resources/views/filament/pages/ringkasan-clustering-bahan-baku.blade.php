<x-filament-panels::page>

{{-- ── Belum ada hasil ─────────────────────────────────────────────────── --}}
@if(! $hasResult)
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-12 text-center">
        <svg class="w-14 h-14 text-gray-300 dark:text-gray-600 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
        </svg>
        <p class="text-base font-semibold text-gray-700 dark:text-gray-200 mb-2">Belum Ada Laporan Klasterisasi Bahan Baku</p>
        <p class="text-sm text-gray-400 max-w-md mx-auto">
            Jalankan klasterisasi di halaman
            <span class="font-semibold text-primary-600">Klasterisasi Bahan Baku</span>
            terlebih dahulu, lalu tekan tombol
            <span class="font-semibold text-primary-600">"Perbarui Data Klasterisasi Bahan Baku"</span>
            di kanan atas untuk menampilkan laporan.
        </p>
    </div>

@else
{{-- ════════════════════════════════════════════════════════════════════ --}}
{{-- LAPORAN — maks. 3 entry, unik per rentang tanggal                    --}}
{{-- ════════════════════════════════════════════════════════════════════ --}}

    <div class="flex items-center justify-between mb-6">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Menampilkan
            <span class="font-bold text-primary-600 dark:text-primary-400">{{ count($results) }}</span>
            laporan klasterisasi bahan baku terakhir (rentang tanggal data penggunaan berbeda)
        </p>
    </div>

    @foreach($results as $idx => $result)
    @php
        $tableRows    = $result['table_rows']    ?? [];
        $rataRata     = $result['rata_rata_table'] ?? [];
        $labelNo      = $idx + 1;
        $labelTerbaru = $idx === 0 ? ' — Terbaru' : '';
        $bestK        = $result['best_k']            ?? 0;
        $silScore     = $result['silhouette_score']   ?? 0.0;
        $totalIng     = $result['total_ingredients']  ?? 0;
    @endphp

    {{-- ── Panel per hasil klasterisasi ──────────────────────────────── --}}
    <div class="rounded-xl border {{ $idx === 0 ? 'border-primary-300 dark:border-primary-700' : 'border-gray-200 dark:border-gray-700' }} bg-white dark:bg-gray-800 mb-10 overflow-hidden shadow-sm">

        {{-- Header panel --}}
        <div class="px-6 py-4 {{ $idx === 0 ? 'bg-primary-50 dark:bg-primary-900/20 border-b border-primary-200 dark:border-primary-700' : 'bg-gray-50 dark:bg-gray-700/30 border-b border-gray-200 dark:border-gray-700' }}">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full text-sm font-bold
                            {{ $idx === 0 ? 'bg-primary-600 text-white' : 'bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-200' }}">
                            {{ $labelNo }}
                        </span>
                        <h2 class="text-base font-bold {{ $idx === 0 ? 'text-primary-700 dark:text-primary-300' : 'text-gray-800 dark:text-white' }}">
                            Laporan Klasterisasi Bahan Baku ke-{{ $labelNo }}{{ $labelTerbaru }}
                        </h2>
                    </div>
                    <div class="flex flex-wrap gap-x-5 gap-y-1 text-xs text-gray-500 dark:text-gray-400 pl-9">
                        <span>
                            <span class="font-medium text-gray-600 dark:text-gray-300">Dijalankan:</span>
                            {{ $result['run_at'] ?? '-' }}
                        </span>
                        <span>
                            <span class="font-medium text-gray-600 dark:text-gray-300">Data yang digunakan:</span>
                            {{ \Carbon\Carbon::parse($result['input_date_from'] ?? '')->translatedFormat('d M Y') }}
                            s/d
                            {{ \Carbon\Carbon::parse($result['input_date_to'] ?? '')->translatedFormat('d M Y') }}
                        </span>
                        <span>
                            <span class="font-medium text-gray-600 dark:text-gray-300">Data aktual:</span>
                            <span class="text-primary-600 dark:text-primary-400 font-semibold">
                                {{ $result['date_from'] ?? '-' }} s/d {{ $result['date_to'] ?? '-' }}
                            </span>
                        </span>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs text-gray-400 dark:text-gray-500 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-full px-3 py-1">
                        K={{ $bestK }} · Sil={{ number_format($silScore, 3) }} · {{ $totalIng }} bahan baku
                    </span>
                </div>
            </div>
        </div>

        {{-- ── TABEL KLASTERISASI BAHAN BAKU ────────────────────────── --}}
        @if(count($tableRows))
        <div class="px-6 pt-6 pb-2">
            <h3 class="text-sm font-bold text-gray-800 dark:text-white mb-1">
                Tabel Klasterisasi Bahan Baku Berdasarkan Jumlah Penggunaan
            </h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                Diurutkan berdasarkan klaster kemudian jumlah penggunaan tertinggi. Data:
                {{ $result['date_from'] ?? '-' }} s/d {{ $result['date_to'] ?? '-' }}
            </p>
        </div>
        <div class="overflow-x-auto mb-6">
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
                    @foreach($tableRows as $ri => $row)
                    <tr style="background-color:{{ $ri % 2 === 0 ? '#ffffff' : '#f8fafc' }}; border-bottom:1px solid #e2e8f0;">
                        <td style="padding:11px 18px; text-align:right; font-size:0.8rem; color:#9ca3af;">{{ $ri + 1 }}</td>
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
        @endif

        {{-- ── TABEL RATA-RATA PER KLASTER ──────────────────────────── --}}
        @if(count($rataRata))
        <div class="px-6 pb-2">
            <h3 class="text-sm font-bold text-gray-800 dark:text-white mb-1">
                Tabel Rata-rata Jumlah Penggunaan Bahan Baku per Klaster
            </h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                Diurutkan berdasarkan rata-rata jumlah penggunaan tertinggi ke terendah
            </p>
        </div>
        <div class="overflow-x-auto mb-4">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background-color:#1e40af;">
                        <th style="padding:12px 18px; text-align:center; font-size:0.7rem; font-weight:600; color:#ffffff; text-transform:uppercase; letter-spacing:0.06em; white-space:nowrap;">Klaster</th>
                        <th style="padding:12px 18px; text-align:right;  font-size:0.7rem; font-weight:600; color:#ffffff; text-transform:uppercase; letter-spacing:0.06em; white-space:nowrap;">Rata-rata Jumlah Penggunaan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rataRata as $ri => $row)
                    <tr style="background-color:{{ $ri % 2 === 0 ? '#ffffff' : '#f8fafc' }}; border-bottom:1px solid #e2e8f0;">
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
        @endif

        {{-- ── VISUALISASI 1: Rata-rata per Klaster ────────────────── --}}
        @if(! empty($result['charts']['rata_klaster']))
        <div class="border-t border-gray-100 dark:border-gray-700 px-6 pt-5 pb-2">
            <h3 class="text-sm font-bold text-gray-800 dark:text-white mb-1">
                Visualisasi Rata-rata Jumlah Penggunaan Bahan Baku per Klaster
            </h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                Diagram batang perbandingan rata-rata penggunaan tiap klaster. Semakin tinggi batang, semakin aktif bahan baku dalam klaster tersebut.
            </p>
        </div>
        <div class="px-6 pb-5">
            <img src="data:image/png;base64,{{ $result['charts']['rata_klaster'] }}"
                 alt="Rata-rata per Klaster" class="w-full rounded-lg border border-gray-100 dark:border-gray-700"/>
        </div>
        <div class="px-6 pb-6">
            <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                <span class="font-semibold text-gray-700 dark:text-gray-300">Keterangan:</span>
                Semakin tinggi nilai rata-rata jumlah penggunaan bahan baku pada suatu klaster, maka bahan baku dalam klaster tersebut memiliki aktivitas penggunaan yang relatif lebih tinggi. Informasi ini dapat digunakan sebagai dasar untuk menentukan prioritas pemantauan stok dan perencanaan pengadaan bahan baku.
            </p>
        </div>
        @endif

        {{-- ── VISUALISASI 2: Jumlah per Bahan Baku (colored by Klaster) ──── --}}
        @if(! empty($result['charts']['bar']))
        <div class="border-t border-gray-100 dark:border-gray-700 px-6 pt-5 pb-2">
            <h3 class="text-sm font-bold text-gray-800 dark:text-white mb-1">
                Visualisasi Jumlah Penggunaan Bahan Baku Berdasarkan Hasil Klasterisasi
            </h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                Setiap batang mewakili total penggunaan bahan baku, diwarnai berdasarkan klaster yang diperoleh dari K-Means.
            </p>
        </div>
        <div class="px-6 pb-6">
            <img src="data:image/png;base64,{{ $result['charts']['bar'] }}"
                 alt="Jumlah per Bahan Baku" class="w-full rounded-lg border border-gray-100 dark:border-gray-700"/>
        </div>
        @endif

    </div>{{-- end panel --}}
    @endforeach

@endif {{-- end hasResult --}}

</x-filament-panels::page>
