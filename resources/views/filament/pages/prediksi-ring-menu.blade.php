<x-filament-panels::page>

{{-- ── Belum ada hasil ─────────────────────────────────────────────────── --}}
@if(! $hasResult)
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-12 text-center">
        <svg class="w-14 h-14 text-gray-300 dark:text-gray-600 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
        </svg>
        <p class="text-base font-semibold text-gray-700 dark:text-gray-200 mb-2">Belum Ada Laporan Prediksi</p>
        <p class="text-sm text-gray-400 max-w-md mx-auto">
            Jalankan prediksi di halaman
            <span class="font-semibold text-primary-600">Prediksi Menu</span>
            terlebih dahulu, lalu tekan tombol
            <span class="font-semibold text-primary-600">"Perbarui Data Prediksi Penjualan Menu"</span>
            di kanan atas untuk menampilkan laporan hasil prediksi.
        </p>
    </div>

@else
{{-- ════════════════════════════════════════════════════════════════════ --}}
{{-- LAPORAN PREDIKSI — maks. 3 entry, unik per rentang tanggal          --}}
{{-- ════════════════════════════════════════════════════════════════════ --}}

    {{-- Keterangan jumlah laporan --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Menampilkan
                <span class="font-bold text-primary-600 dark:text-primary-400">{{ count($results) }}</span>
                laporan prediksi terakhir (rentang tanggal data penjualan berbeda)
            </p>
        </div>
    </div>

    @foreach($results as $idx => $result)
    @php
        $predictions  = $result['predictions']   ?? [];
        $labelNo      = $idx + 1;
        $labelTerbaru = $idx === 0 ? ' — Terbaru' : '';

        // Ambil tanggal forecast dari prediksi pertama
        $forecastDays = [];
        if (! empty($predictions)) {
            $forecastDays = $predictions[0]['forecast'] ?? [];
        }
    @endphp

    {{-- ── Panel per prediksi ─────────────────────────────────────────── --}}
    <div class="rounded-xl border {{ $idx === 0 ? 'border-primary-300 dark:border-primary-700' : 'border-gray-200 dark:border-gray-700' }} bg-white dark:bg-gray-800 mb-8 overflow-hidden shadow-sm">

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
                            Laporan Prediksi ke-{{ $labelNo }}{{ $labelTerbaru }}
                        </h2>
                    </div>
                    <div class="flex flex-wrap gap-x-5 gap-y-1 text-xs text-gray-500 dark:text-gray-400 pl-9">
                        <span>
                            <span class="font-medium text-gray-600 dark:text-gray-300">Dijalankan:</span>
                            {{ $result['run_at'] ?? '-' }}
                        </span>
                        <span>
                            <span class="font-medium text-gray-600 dark:text-gray-300">Data penjualan yang digunakan:</span>
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
                    {{ $result['total_menu'] ?? 0 }} menu · {{ $result['forecast_days'] ?? 2 }} hari prediksi
                </span>
            </div>
        </div>

        {{-- ── Tabel Laporan Hasil Prediksi Penjualan + MAE ─────────────── --}}
        @if(count($predictions))
        <div class="mx-6 mt-6 mb-6 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60">
                <h3 class="text-sm font-bold text-gray-800 dark:text-white">
                    Laporan Hasil Prediksi Penjualan 2 Hari ke Depan
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                    Nilai MAE (Mean Absolute Error) menunjukkan akurasi model — semakin kecil semakin akurat.
                </p>
            </div>
            <div class="overflow-x-auto">
                <table style="width:100%; border-collapse:collapse;">
                    <thead>
                        <tr style="background-color:#1e40af;">
                            <th style="padding:12px 20px; text-align:left; font-size:0.72rem; font-weight:600; color:#ffffff; text-transform:uppercase; letter-spacing:0.06em; white-space:nowrap;">Nama Menu</th>
                            @if(count($forecastDays) > 0)
                            <th style="padding:12px 20px; text-align:center; font-size:0.72rem; font-weight:600; color:#ffffff; text-transform:uppercase; letter-spacing:0.06em; white-space:nowrap;">
                                Hari 1 &mdash; {{ $forecastDays[0]['hari'] ?? '' }}, {{ $forecastDays[0]['tanggal'] ?? '' }}
                            </th>
                            @endif
                            @if(count($forecastDays) > 1)
                            <th style="padding:12px 20px; text-align:center; font-size:0.72rem; font-weight:600; color:#ffffff; text-transform:uppercase; letter-spacing:0.06em; white-space:nowrap;">
                                Hari 2 &mdash; {{ $forecastDays[1]['hari'] ?? '' }}, {{ $forecastDays[1]['tanggal'] ?? '' }}
                            </th>
                            @endif
                            <th style="padding:12px 20px; text-align:right; font-size:0.72rem; font-weight:600; color:#ffffff; text-transform:uppercase; letter-spacing:0.06em; white-space:nowrap;">Total Prediksi</th>
                            <th style="padding:12px 20px; text-align:right; font-size:0.72rem; font-weight:600; color:#ffffff; text-transform:uppercase; letter-spacing:0.06em; white-space:nowrap;">MAE</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($predictions as $ri => $pred)
                        @php
                            $fc = $pred['forecast'] ?? [];
                        @endphp
                        <tr style="background-color:{{ $ri % 2 === 0 ? '#ffffff' : '#f8fafc' }}; border-bottom:1px solid #e2e8f0;">
                            <td style="padding:12px 20px; font-size:0.875rem; font-weight:600; color:#1e40af; white-space:nowrap;">
                                {{ $pred['nama_menu'] }}
                            </td>

                            {{-- Hari 1 --}}
                            @if(isset($fc[0]))
                            <td style="padding:12px 20px; text-align:center;">
                                <span style="display:inline-block; background:#e0e7ff; color:#3730a3; padding:4px 14px; border-radius:9999px; font-size:0.875rem; font-weight:700;">
                                    {{ $fc[0]['prediksi'] ?? '-' }} unit
                                </span>
                                <div style="font-size:0.7rem; color:#94a3b8; margin-top:3px;">
                                    {{ $fc[0]['day_type'] ?? '' }}
                                </div>
                            </td>
                            @else
                            <td style="padding:12px 20px; text-align:center; color:#94a3b8; font-size:0.875rem;">—</td>
                            @endif

                            {{-- Hari 2 --}}
                            @if(isset($fc[1]))
                            <td style="padding:12px 20px; text-align:center;">
                                <span style="display:inline-block; background:#e0e7ff; color:#3730a3; padding:4px 14px; border-radius:9999px; font-size:0.875rem; font-weight:700;">
                                    {{ $fc[1]['prediksi'] ?? '-' }} unit
                                </span>
                                <div style="font-size:0.7rem; color:#94a3b8; margin-top:3px;">
                                    {{ $fc[1]['day_type'] ?? '' }}
                                </div>
                            </td>
                            @elseif(count($forecastDays) > 1)
                            <td style="padding:12px 20px; text-align:center; color:#94a3b8; font-size:0.875rem;">—</td>
                            @endif

                            {{-- Total prediksi --}}
                            <td style="padding:12px 20px; text-align:right; font-size:0.875rem; font-weight:700; color:#0f172a;">
                                {{ number_format($pred['total_forecast'] ?? 0, 0) }} unit
                            </td>

                            {{-- MAE --}}
                            <td style="padding:12px 20px; text-align:right; font-size:0.875rem; font-weight:700; color:#0f172a;">
                                {{ isset($pred['mae']) ? number_format($pred['mae'], 2) : '-' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- ── Analisis Weekday vs Weekend ──────────────────────────────── --}}
        @if(! empty($result['chart_feature_importance']))
        <div class="mx-6 mb-6 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60">
                <h3 class="text-sm font-bold text-gray-800 dark:text-white">
                    Analisis Rata-rata Penjualan per Menu: Weekday vs Weekend
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                    Perbandingan rata-rata jumlah penjualan tiap menu pada hari kerja dan akhir pekan berdasarkan data historis yang digunakan.
                </p>
            </div>
            <div class="p-5">
                <img src="data:image/png;base64,{{ $result['chart_feature_importance'] }}"
                     alt="Analisis Weekday vs Weekend" class="w-full rounded-lg"/>
            </div>
        </div>
        @endif

    </div>{{-- end panel --}}
    @endforeach

@endif {{-- end hasResult --}}

</x-filament-panels::page>
