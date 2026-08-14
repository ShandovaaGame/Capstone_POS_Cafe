<x-filament-panels::page>

{{-- ══════════════════════════════════════════════════════════════════════ --}}
{{-- INPUT RENTANG TANGGAL                                                   --}}
{{-- ══════════════════════════════════════════════════════════════════════ --}}
<div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 mb-6 shadow-sm overflow-hidden">

    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60">
        <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">Rentang Tanggal Data Penjualan</p>
        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Pilih periode data transaksi yang akan dianalisis untuk Association Rule (minimal 3 bulan)</p>
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
            @if(! $this->isDatesValid())
                <div class="rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 px-4 py-3">
                    <p class="text-xs text-amber-700 dark:text-amber-300">
                        @if(empty($inputDateFrom) && empty($inputDateTo))
                            Pilih <strong>Dari Tanggal</strong> dan <strong>Sampai Tanggal</strong> dengan rentang minimal <strong>3 bulan</strong>, lalu tekan <strong>"Jalankan Association Rule"</strong> di kanan atas.
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
                        Rentang tanggal valid. Tekan <strong>"Jalankan Association Rule"</strong> di kanan atas untuk memulai analisis.
                    </p>
                </div>
            @endif
        </div>
    </div>

</div>

{{-- ── Error ──────────────────────────────────────────────────────────── --}}
@if($errorMsg)
    <div class="rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 p-4 mb-6">
        <p class="text-sm font-semibold text-red-700 dark:text-red-300">Association Rule gagal</p>
        <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $errorMsg }}</p>
        <p class="text-xs text-red-500 mt-2">
            Pastikan FastAPI sudah berjalan:
            <code class="bg-red-100 dark:bg-red-900 px-1.5 py-0.5 rounded font-mono">cd datamining &amp;&amp; uvicorn api:app --port 8001</code>
        </p>
    </div>
@endif

{{-- ── Belum ada hasil ────────────────────────────────────────────────── --}}
@if(! $hasResult)
    <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-10 text-center">
        <p class="text-base font-semibold text-gray-700 dark:text-gray-200">Belum Ada Hasil Association Rule</p>
        <p class="text-sm text-gray-400 mt-2">
            @if(! $this->isDatesValid())
                Isi rentang tanggal data riwayat penjualan (minimal 3 bulan) di atas, lalu tekan
                <span class="font-semibold">"Jalankan Association Rule"</span>.
            @else
                Rentang tanggal sudah valid. Tekan
                <span class="font-semibold">"Jalankan Association Rule"</span> di kanan atas untuk memulai.
            @endif
        </p>
        <div class="mt-8 grid grid-cols-3 gap-4 max-w-2xl mx-auto text-left">
            <div class="rounded-lg border border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 p-4">
                <p class="text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Support</p>
                <p class="text-xs text-gray-400">Seberapa sering kombinasi menu muncul dari seluruh transaksi.</p>
            </div>
            <div class="rounded-lg border border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 p-4">
                <p class="text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Confidence</p>
                <p class="text-xs text-gray-400">Seberapa sering menu B dipesan setelah menu A dipesan lebih dahulu.</p>
            </div>
            <div class="rounded-lg border border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 p-4">
                <p class="text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Lift</p>
                <p class="text-xs text-gray-400">Kekuatan asosiasi. Lift > 1 berarti hubungan positif.</p>
            </div>
        </div>
    </div>

@else
{{-- ══════════════════════════════════════════════════════════════════════ --}}
{{-- HASIL ASSOCIATION RULE                                                 --}}
{{-- ══════════════════════════════════════════════════════════════════════ --}}

    {{-- ── Info run ─────────────────────────────────────────────────── --}}
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

    {{-- ── Stat bar ────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-4 gap-4 mb-8">
        @foreach([
            ['label' => 'Total Rules',     'value' => $totalRules],
            ['label' => 'Total Transaksi', 'value' => $totalTransactions],
            ['label' => 'Min Support',     'value' => number_format($minSupport * 100, 1) . '%'],
            ['label' => 'Min Confidence',  'value' => number_format($minConfidence * 100, 1) . '%'],
        ] as $s)
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-6 py-5">
                <p class="text-xs text-gray-400 uppercase tracking-widest mb-2">{{ $s['label'] }}</p>
                <p class="text-3xl font-bold text-gray-800 dark:text-white">{{ $s['value'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- TABEL TOP 8 ASSOCIATION RULES                                   --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    @if(count($rules))
    <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 mb-8 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
            <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">LAPORAN HASIL ASSOCIATION RULE MENU CAFE</h2>
            <p class="text-xs text-gray-400 mt-0.5">
                Aturan terarah: "Jika memesan A maka memesan B" ≠ "Jika memesan B maka memesan A" — diurutkan berdasarkan Lift tertinggi
            </p>
        </div>
        <div class="overflow-x-auto">
            <table style="width:100%; border-collapse:collapse; font-size:0.8rem;">
                <thead>
                    <tr style="background-color:#1d4ed8;">
                        <th style="padding:10px 14px; text-align:center; font-size:0.7rem; font-weight:700; color:#ffffff; text-transform:uppercase; letter-spacing:0.05em; border:1px solid #3b82f6; width:40px;">No</th>
                        <th style="padding:10px 14px; text-align:left;   font-size:0.7rem; font-weight:700; color:#ffffff; text-transform:uppercase; letter-spacing:0.05em; border:1px solid #3b82f6;">Pesan Pertama (A)</th>
                        <th style="padding:10px 14px; text-align:left;   font-size:0.7rem; font-weight:700; color:#ffffff; text-transform:uppercase; letter-spacing:0.05em; border:1px solid #3b82f6;">Pesan Berikutnya (B)</th>
                        <th style="padding:10px 14px; text-align:right;  font-size:0.7rem; font-weight:700; color:#ffffff; text-transform:uppercase; letter-spacing:0.05em; border:1px solid #3b82f6;">Jml A</th>
                        <th style="padding:10px 14px; text-align:right;  font-size:0.7rem; font-weight:700; color:#ffffff; text-transform:uppercase; letter-spacing:0.05em; border:1px solid #3b82f6;">Jml B</th>
                        <th style="padding:10px 14px; text-align:right;  font-size:0.7rem; font-weight:700; color:#ffffff; text-transform:uppercase; letter-spacing:0.05em; border:1px solid #3b82f6;">Jml A→B</th>
                        <th style="padding:10px 14px; text-align:right;  font-size:0.7rem; font-weight:700; color:#ffffff; text-transform:uppercase; letter-spacing:0.05em; border:1px solid #3b82f6;">Support</th>
                        <th style="padding:10px 14px; text-align:right;  font-size:0.7rem; font-weight:700; color:#ffffff; text-transform:uppercase; letter-spacing:0.05em; border:1px solid #3b82f6;">Confidence</th>
                        <th style="padding:10px 14px; text-align:right;  font-size:0.7rem; font-weight:700; color:#ffffff; text-transform:uppercase; letter-spacing:0.05em; border:1px solid #3b82f6;">Lift</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rules as $i => $rule)
                        @php
                            $liftColor = $rule['lift'] >= 2
                                ? 'color:#15803d; font-weight:700;'
                                : ($rule['lift'] >= 1
                                    ? 'color:#b45309; font-weight:700;'
                                    : 'color:#dc2626; font-weight:700;');
                            $rowBg = $i % 2 === 0 ? 'background-color:#f8fafc;' : 'background-color:#ffffff;';
                        @endphp
                        <tr style="{{ $rowBg }}">
                            <td style="padding:10px 14px; text-align:center; color:#6b7280; font-size:0.75rem; border:1px solid #e2e8f0;">{{ $i + 1 }}</td>
                            <td style="padding:10px 14px; text-align:left; border:1px solid #e2e8f0;">
                                <span style="display:inline-block; background-color:#fef08a; color:#713f12; padding:3px 10px; border-radius:4px; font-size:0.75rem; font-weight:600;">
                                    {{ $rule['menu_pertama'] }}
                                </span>
                            </td>
                            <td style="padding:10px 14px; text-align:left; border:1px solid #e2e8f0;">
                                <span style="display:inline-block; background-color:#dcfce7; color:#15803d; padding:3px 10px; border-radius:4px; font-size:0.75rem; font-weight:600;">
                                    {{ $rule['menu_kedua'] }}
                                </span>
                            </td>
                            <td style="padding:10px 14px; text-align:right; color:#374151; font-size:0.75rem; border:1px solid #e2e8f0;">{{ number_format($rule['jumlah_menu_pertama']) }}</td>
                            <td style="padding:10px 14px; text-align:right; color:#374151; font-size:0.75rem; border:1px solid #e2e8f0;">{{ number_format($rule['jumlah_menu_kedua']) }}</td>
                            <td style="padding:10px 14px; text-align:right; color:#1d4ed8; font-weight:600; font-size:0.75rem; border:1px solid #e2e8f0;">{{ number_format($rule['jumlah_bersamaan']) }}</td>
                            <td style="padding:10px 14px; text-align:right; color:#374151; font-size:0.75rem; border:1px solid #e2e8f0;">{{ number_format($rule['support'] * 100, 2) }}%</td>
                            <td style="padding:10px 14px; text-align:right; color:#374151; font-size:0.75rem; border:1px solid #e2e8f0;">{{ number_format($rule['confidence'] * 100, 2) }}%</td>
                            <td style="padding:10px 14px; text-align:right; font-size:0.8rem; border:1px solid #e2e8f0; {{ $liftColor }}">{{ number_format($rule['lift'], 2) }}</td>
                        </tr>
                        <tr style="background-color:#eff6ff;">
                            <td style="border:1px solid #e2e8f0;"></td>
                            <td colspan="8" style="padding:8px 14px; border:1px solid #e2e8f0;">
                                <span style="font-size:0.75rem; color:#3b82f6; font-style:italic; line-height:1.6;">
                                    💡 {{ $rule['interpretasi'] }}
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
    {{-- VISUALISASI Top Rules                                           --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    @if($chartTopRules)
    <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 mb-6 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
            <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">Visualisasi Top Rules Berdasarkan Lift</h2>
            <p class="text-xs text-gray-400 mt-0.5">Rule dengan nilai lift tertinggi menunjukkan asosiasi terkuat antar menu</p>
        </div>
        <div class="p-4">
            <img src="data:image/png;base64,{{ $chartTopRules }}" alt="Top Rules Chart" class="w-full rounded"/>
        </div>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- VISUALISASI Support vs Confidence                               --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    @if($chartSupConf)
    <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 mb-6 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
            <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Support vs Confidence</h2>
            <p class="text-xs text-gray-400 mt-0.5">Sebaran rules berdasarkan nilai support dan confidence</p>
        </div>
        <div class="p-4">
            <img src="data:image/png;base64,{{ $chartSupConf }}" alt="Support vs Confidence" class="w-full rounded"/>
        </div>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- VISUALISASI Frekuensi Kemunculan Tiap Menu                      --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    @if($chartFreqItem)
    <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 mb-8 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
            <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Visualisasi Frekuensi Kemunculan Tiap Menu</h2>
            <p class="text-xs text-gray-400 mt-0.5">Frekuensi kemunculan tiap menu dalam transaksi</p>
        </div>
        <div class="p-4">
            <img src="data:image/png;base64,{{ $chartFreqItem }}" alt="Frequent Itemsets" class="w-full rounded"/>
        </div>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- TABEL FREQUENT 1-ITEMSETS                                       --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    @if(count($freq1Itemsets))
    <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 mb-6 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
            <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Frequent 1-Itemsets</h2>
            <p class="text-xs text-gray-400 mt-0.5">Daftar menu yang sering muncul sendiri dalam transaksi</p>
        </div>
        <div class="overflow-x-auto">
            <table style="width:100%; border-collapse:collapse; font-size:0.78rem;">
                <thead>
                    <tr style="background-color:#1e40af;">
                        <th style="padding:9px 14px; text-align:left;  font-size:0.7rem; font-weight:600; color:#ffffff; text-transform:uppercase; letter-spacing:0.05em; border:1px solid #3b82f6;">Menu</th>
                        <th style="padding:9px 14px; text-align:right; font-size:0.7rem; font-weight:600; color:#ffffff; text-transform:uppercase; letter-spacing:0.05em; border:1px solid #3b82f6;">Support</th>
                        <th style="padding:9px 14px; text-align:right; font-size:0.7rem; font-weight:600; color:#ffffff; text-transform:uppercase; letter-spacing:0.05em; border:1px solid #3b82f6;">Jumlah Transaksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($freq1Itemsets as $idx => $fi)
                    <tr style="{{ $idx % 2 === 0 ? 'background-color:#f8fafc;' : 'background-color:#ffffff;' }}">
                        <td style="padding:8px 14px; text-align:left;  color:#1d4ed8; font-weight:500; border:1px solid #e2e8f0;">{{ $fi['item'] }}</td>
                        <td style="padding:8px 14px; text-align:right; color:#374151; border:1px solid #e2e8f0;">{{ number_format($fi['support'] * 100, 2) }}%</td>
                        <td style="padding:8px 14px; text-align:right; color:#374151; font-weight:600; border:1px solid #e2e8f0;">{{ number_format($fi['jumlah_kemunculan']) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- TABEL FREQUENT 2-ITEMSETS                                       --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    @if(count($freq2Itemsets))
    <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 mb-8 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
            <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Frequent 2-Itemsets (Terurut)</h2>
            <p class="text-xs text-gray-400 mt-0.5">Daftar 2 menu yang sering dipesan berurutan (A → B berarti A dipesan sebelum B)</p>
        </div>
        <div class="overflow-x-auto">
            <table style="width:100%; border-collapse:collapse; font-size:0.78rem;">
                <thead>
                    <tr style="background-color:#1e40af;">
                        <th style="padding:9px 14px; text-align:left;  font-size:0.7rem; font-weight:600; color:#ffffff; text-transform:uppercase; letter-spacing:0.05em; border:1px solid #3b82f6;">Urutan Menu (A → B)</th>
                        <th style="padding:9px 14px; text-align:right; font-size:0.7rem; font-weight:600; color:#ffffff; text-transform:uppercase; letter-spacing:0.05em; border:1px solid #3b82f6;">Support</th>
                        <th style="padding:9px 14px; text-align:right; font-size:0.7rem; font-weight:600; color:#ffffff; text-transform:uppercase; letter-spacing:0.05em; border:1px solid #3b82f6;">Jumlah Transaksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($freq2Itemsets as $idx => $fi)
                    <tr style="{{ $idx % 2 === 0 ? 'background-color:#f8fafc;' : 'background-color:#ffffff;' }}">
                        <td style="padding:8px 14px; text-align:left;  color:#1d4ed8; font-weight:500; border:1px solid #e2e8f0;">{{ $fi['items'] }}</td>
                        <td style="padding:8px 14px; text-align:right; color:#374151; border:1px solid #e2e8f0;">{{ number_format($fi['support'] * 100, 2) }}%</td>
                        <td style="padding:8px 14px; text-align:right; color:#374151; font-weight:600; border:1px solid #e2e8f0;">{{ number_format($fi['jumlah_kemunculan']) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- TAHAPAN ANALISIS DATA (dipindah ke bawah)                       --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    @if(count($preprocessLogs))
    <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden mb-8">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
            <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">Tahapan Analisis Data</h2>
            <p class="text-xs text-gray-400 mt-0.5">Proses yang berlangsung selama association rule mining</p>
        </div>
        <div class="divide-y divide-gray-50 dark:divide-gray-700/40">
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
