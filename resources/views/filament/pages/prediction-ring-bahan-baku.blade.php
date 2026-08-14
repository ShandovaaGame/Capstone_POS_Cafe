<x-filament-panels::page>

{{-- ── Belum ada hasil ─────────────────────────────────────────────────── --}}
@if(! $hasResult)
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-12 text-center">
        <svg class="w-14 h-14 text-gray-300 dark:text-gray-600 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23-.693L5 14.5m14.8.8l1.402 1.402c1 1 .03 2.698-1.338 2.698H4.136c-1.368 0-2.338-1.698-1.338-2.698L4.2 15.3"/>
        </svg>
        <p class="text-base font-semibold text-gray-700 dark:text-gray-200 mb-2">Belum Ada Laporan Prediksi Bahan Baku</p>
        <p class="text-sm text-gray-400 max-w-md mx-auto">
            Jalankan prediksi di halaman
            <span class="font-semibold text-primary-600">Prediksi Bahan Baku</span>
            terlebih dahulu, lalu tekan tombol
            <span class="font-semibold text-primary-600">"Perbarui Data Prediksi Penggunaan Bahan Baku"</span>
            di kanan atas untuk menampilkan laporan.
        </p>
    </div>

@else
{{-- ════════════════════════════════════════════════════════════════════ --}}
{{-- LAPORAN PREDIKSI — maks. 3 entry, unik per rentang tanggal           --}}
{{-- ════════════════════════════════════════════════════════════════════ --}}

    <div class="flex items-center justify-between mb-6">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Menampilkan
            <span class="font-bold text-primary-600 dark:text-primary-400">{{ count($results) }}</span>
            laporan prediksi bahan baku terakhir (rentang tanggal data penggunaan berbeda)
        </p>
    </div>

    @foreach($results as $idx => $result)
    @php
        $predictions  = $result['predictions']   ?? [];
        $summaryTable = $result['summary_table']  ?? [];
        $labelNo      = $idx + 1;
        $labelTerbaru = $idx === 0 ? ' — Terbaru' : '';

        // Ambil tanggal forecast dari prediksi pertama
        $forecastDays = [];
        if (! empty($predictions)) {
            $forecastDays = $predictions[0]['forecast'] ?? [];
        }
    @endphp

    {{-- ── Panel per prediksi ─────────────────────────────────────────── --}}
    <div class="rounded-xl border {{ $idx === 0 ? 'border-primary-300 dark:border-primary-700' : 'border-gray-200 dark:border-gray-700' }} bg-white dark:bg-gray-800 mb-8 shadow-sm">

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
                            Laporan Prediksi Bahan Baku ke-{{ $labelNo }}{{ $labelTerbaru }}
                        </h2>
                    </div>
                    <div class="flex flex-wrap gap-x-5 gap-y-1 text-xs text-gray-500 dark:text-gray-400 pl-9">
                        <span>
                            <span class="font-medium text-gray-600 dark:text-gray-300">Dijalankan:</span>
                            {{ $result['run_at'] ?? '-' }}
                        </span>
                        <span>
                            <span class="font-medium text-gray-600 dark:text-gray-300">Data penggunaan yang digunakan:</span>
                            {{ \Carbon\Carbon::parse($result['input_date_from'] ?? '')->translatedFormat('d M Y') }}
                            s/d
                            {{ \Carbon\Carbon::parse($result['input_date_to'] ?? '')->translatedFormat('d M Y') }}
                        </span>
                        <span>
                            <span class="font-medium text-gray-600 dark:text-gray-300">Periode prediksi:</span>
                            <span class="text-primary-600 dark:text-primary-400 font-semibold">
                                {{ $result['date_forecast_from'] ?? '-' }} s/d {{ $result['date_forecast_to'] ?? '-' }}
                            </span>
                        </span>
                    </div>
                </div>
                <span class="text-xs text-gray-400 dark:text-gray-500 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-full px-3 py-1">
                    {{ $result['total_ingredients'] ?? 0 }} bahan baku · {{ $result['forecast_days'] ?? 2 }} hari prediksi
                </span>
            </div>
        </div>

        {{-- ── TABEL LAPORAN PREDIKSI + MAE, RMSE, MAPE, SMAPE ─────────── --}}
        @if(count($predictions))
        <div class="px-6 pt-5 pb-3 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/40">
            <h3 class="text-sm font-bold text-gray-800 dark:text-white">
                Laporan Prediksi Jumlah Penggunaan Bahan Baku 2 Hari ke Depan
            </h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                Nilai MAE, RMSE, MAPE, dan SMAPE menunjukkan akurasi model Prophet untuk tiap bahan baku — semakin kecil semakin akurat.
            </p>
        </div>
        <div class="overflow-x-auto mb-6" style="overflow-x:auto;">
            <table style="width:max-content; min-width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background-color:#1e40af;">
                        <th style="padding:11px 16px; text-align:left;   font-size:0.7rem; font-weight:600; color:#ffffff; text-transform:uppercase; letter-spacing:0.06em; white-space:nowrap; min-width:160px;">Nama Bahan Baku</th>
                        <th style="padding:11px 12px; text-align:center; font-size:0.7rem; font-weight:600; color:#ffffff; text-transform:uppercase; letter-spacing:0.06em; white-space:nowrap; min-width:60px;">Satuan</th>
                        @if(count($forecastDays) > 0)
                        <th style="padding:11px 12px; text-align:center; font-size:0.7rem; font-weight:600; color:#ffffff; text-transform:uppercase; letter-spacing:0.06em; min-width:110px;">
                            Hari 1<br><span style="font-weight:400; font-size:0.65rem; opacity:0.85;">{{ $forecastDays[0]['hari'] ?? '' }}, {{ $forecastDays[0]['tanggal'] ?? '' }}</span>
                        </th>
                        @endif
                        @if(count($forecastDays) > 1)
                        <th style="padding:11px 12px; text-align:center; font-size:0.7rem; font-weight:600; color:#ffffff; text-transform:uppercase; letter-spacing:0.06em; min-width:110px;">
                            Hari 2<br><span style="font-weight:400; font-size:0.65rem; opacity:0.85;">{{ $forecastDays[1]['hari'] ?? '' }}, {{ $forecastDays[1]['tanggal'] ?? '' }}</span>
                        </th>
                        @endif
                        <th style="padding:11px 12px; text-align:right;  font-size:0.7rem; font-weight:600; color:#ffffff; text-transform:uppercase; letter-spacing:0.06em; white-space:nowrap; min-width:70px;">Total</th>
                        <th style="padding:11px 12px; text-align:right;  font-size:0.7rem; font-weight:600; color:#ffffff; text-transform:uppercase; letter-spacing:0.06em; white-space:nowrap; min-width:70px;">MAE</th>
                        <th style="padding:11px 12px; text-align:right;  font-size:0.7rem; font-weight:600; color:#ffffff; text-transform:uppercase; letter-spacing:0.06em; white-space:nowrap; min-width:70px;">RMSE</th>
                        <th style="padding:11px 12px; text-align:right;  font-size:0.7rem; font-weight:600; color:#ffffff; text-transform:uppercase; letter-spacing:0.06em; white-space:nowrap; min-width:85px;">MAPE (%)</th>
                        <th style="padding:11px 12px; text-align:right;  font-size:0.7rem; font-weight:600; color:#ffffff; text-transform:uppercase; letter-spacing:0.06em; white-space:nowrap; min-width:85px;">SMAPE (%)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($predictions as $ri => $pred)
                    @php
                        $fc   = $pred['forecast'] ?? [];
                        $mape = $pred['mape'] ?? 0;
                    @endphp
                    <tr style="background-color:{{ $ri % 2 === 0 ? '#ffffff' : '#f8fafc' }}; border-bottom:1px solid #e2e8f0;">
                        <td style="padding:11px 16px; font-size:0.875rem; font-weight:600; color:#1e40af; white-space:nowrap;">
                            {{ $pred['nama_bahan_baku'] }}
                        </td>
                        <td style="padding:11px 12px; text-align:center;">
                            <span style="display:inline-block; background:#e0e7ff; color:#3730a3; padding:2px 8px; border-radius:9999px; font-size:0.75rem; font-weight:600;">
                                {{ $pred['satuan'] ?? '-' }}
                            </span>
                        </td>

                        {{-- Hari 1 --}}
                        @if(isset($fc[0]))
                        <td style="padding:11px 12px; text-align:center;">
                            <span style="display:inline-block; background:#e0e7ff; color:#3730a3; padding:3px 10px; border-radius:9999px; font-size:0.875rem; font-weight:700;">
                                {{ $fc[0]['prediksi'] ?? '-' }}
                            </span>
                            <div style="font-size:0.7rem; color:#94a3b8; margin-top:3px;">{{ $fc[0]['day_type'] ?? '' }}</div>
                        </td>
                        @elseif(count($forecastDays) > 0)
                        <td style="padding:11px 12px; text-align:center; color:#94a3b8; font-size:0.875rem;">—</td>
                        @endif

                        {{-- Hari 2 --}}
                        @if(isset($fc[1]))
                        <td style="padding:11px 12px; text-align:center;">
                            <span style="display:inline-block; background:#e0e7ff; color:#3730a3; padding:3px 10px; border-radius:9999px; font-size:0.875rem; font-weight:700;">
                                {{ $fc[1]['prediksi'] ?? '-' }}
                            </span>
                            <div style="font-size:0.7rem; color:#94a3b8; margin-top:3px;">{{ $fc[1]['day_type'] ?? '' }}</div>
                        </td>
                        @elseif(count($forecastDays) > 1)
                        <td style="padding:11px 12px; text-align:center; color:#94a3b8; font-size:0.875rem;">—</td>
                        @endif

                        {{-- Total --}}
                        <td style="padding:11px 12px; text-align:right; font-size:0.875rem; font-weight:700; color:#0f172a;">
                            {{ number_format($pred['total_forecast'] ?? 0, 1) }}
                        </td>
                        {{-- MAE --}}
                        <td style="padding:11px 12px; text-align:right; font-size:0.875rem; color:#374151;">
                            {{ number_format($pred['mae'] ?? 0, 2) }}
                        </td>
                        {{-- RMSE --}}
                        <td style="padding:11px 12px; text-align:right; font-size:0.875rem; color:#374151;">
                            {{ number_format($pred['rmse'] ?? 0, 2) }}
                        </td>
                        {{-- MAPE --}}
                        <td style="padding:11px 12px; text-align:right; font-size:0.875rem; font-weight:700;
                            color:{{ $mape <= 10 ? '#16a34a' : ($mape <= 25 ? '#d97706' : '#dc2626') }};">
                            {{ number_format($mape, 2) }}%
                        </td>
                        {{-- SMAPE --}}
                        <td style="padding:11px 12px; text-align:right; font-size:0.875rem; color:#374151;">
                            {{ number_format($pred['smape'] ?? 0, 2) }}%
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        {{-- ── ANALISIS RATA-RATA WEEKDAY VS WEEKEND ────────────────────── --}}
        @if(! empty($result['chart_feature_importance']))
        <div class="mx-6 mb-6 rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60">
                <h3 class="text-sm font-bold text-gray-800 dark:text-white">
                    Analisis Rata-rata Jumlah Penggunaan Bahan Baku: Weekday vs Weekend
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                    Perbandingan rata-rata jumlah penggunaan tiap bahan baku pada hari kerja dan akhir pekan berdasarkan data historis yang digunakan.
                    Memperlihatkan seberapa besar perbedaan pengaruh weekend dan weekday terhadap penggunaan tiap bahan baku.
                </p>
            </div>
            <div class="p-5">
                <img src="data:image/png;base64,{{ $result['chart_feature_importance'] }}"
                     alt="Weekday vs Weekend" class="w-full rounded-lg"/>
            </div>
        </div>
        @endif

    </div>{{-- end panel --}}
    @endforeach

@endif {{-- end hasResult --}}

</x-filament-panels::page>
