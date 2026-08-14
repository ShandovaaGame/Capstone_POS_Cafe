<x-filament-panels::page>

    {{-- ── Error banner ──────────────────────────────────────────────────────── --}}
    @if($errorMsg)
        <div class="rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 p-4 mb-6">
            <p class="text-sm font-semibold text-red-700 dark:text-red-300">{{ $errorMsg }}</p>
            <p class="text-xs text-red-500 dark:text-red-400 mt-1">
                Buka halaman <span class="font-semibold">Asosiatif Menu</span>,
                tekan <span class="font-semibold">"Jalankan Association Rule"</span>, lalu kembali ke sini.
            </p>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════════════════ --}}
    {{-- BELUM ADA DATA                                                            --}}
    {{-- ══════════════════════════════════════════════════════════════════════════ --}}
    @if(! $hasResult)
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-10 text-center">
            <p class="text-base font-semibold text-gray-700 dark:text-gray-200">Belum Ada Hasil Association Rule</p>
            <p class="text-sm text-gray-400 mt-2">
                Jalankan proses di halaman <span class="font-semibold">Asosiatif Menu</span> terlebih dahulu,
                lalu klik <span class="font-semibold">"Perbarui Data Asosiatif Menu"</span> di kanan atas untuk menampilkan hasilnya di sini.
            </p>
        </div>

    @else
    {{-- ══════════════════════════════════════════════════════════════════════════ --}}
    {{-- RINGKASAN — loop atas ≤ 3 hasil                                           --}}
    {{-- ══════════════════════════════════════════════════════════════════════════ --}}

        {{-- Info jumlah hasil ────────────────────────────────────────────────── --}}
        <div class="flex items-center gap-3 mb-6">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 text-xs font-semibold px-3 py-1">
                {{ $this->getResultCount() }} hasil tersimpan
            </span>
            <span class="text-xs text-gray-400">
                Menampilkan hingga 3 hasil association rule terbaru, unik per rentang tanggal.
            </span>
        </div>

        @foreach($results as $rIdx => $res)
        @php
            $rules          = $res['rules']              ?? [];
            $freq1          = $res['freq_1_itemsets']    ?? [];
            $freq2          = $res['freq_2_itemsets']    ?? [];
            $totalRules     = $res['total_rules']        ?? 0;
            $totalTx        = $res['total_transactions'] ?? 0;
            $minSup         = $res['min_support']        ?? 0.0;
            $minConf        = $res['min_confidence']     ?? 0.0;
            $dateFrom       = $res['date_range']['from'] ?? ($res['input_date_from'] ?? '-');
            $dateTo         = $res['date_range']['to']   ?? ($res['input_date_to']   ?? '-');
            $runAt          = $res['last_run_at']        ?? '-';
            $inputFrom      = $res['input_date_from']    ?? '-';
            $inputTo        = $res['input_date_to']      ?? '-';
            $chTopRules     = $res['charts']['top_rules'] ?? null;
            $chFreqItem     = $res['charts']['freq_item'] ?? null;
            $chSupConf      = $res['charts']['sup_conf']  ?? null;
            $labelColors    = ['bg-blue-600','bg-indigo-600','bg-violet-600'];
            $labelBg        = $labelColors[$rIdx] ?? 'bg-gray-600';
        @endphp

        {{-- ── Divider antara hasil ─────────────────────────────────────────── --}}
        @if($rIdx > 0)
            <div class="flex items-center gap-3 my-8">
                <div class="flex-1 border-t-2 border-dashed border-gray-200 dark:border-gray-700"></div>
                <span class="text-xs text-gray-400 dark:text-gray-500 px-2 shrink-0">Hasil Sebelumnya</span>
                <div class="flex-1 border-t-2 border-dashed border-gray-200 dark:border-gray-700"></div>
            </div>
        @endif

        {{-- ── Header hasil ─────────────────────────────────────────────────── --}}
        <div class="flex flex-wrap items-start gap-3 mb-5">
            <span class="inline-flex items-center gap-1.5 rounded-full {{ $labelBg }} text-white text-xs font-bold px-3 py-1.5">
                {{ $rIdx === 0 ? 'Terbaru' : 'Hasil ke-' . ($rIdx + 1) }}
            </span>
            <div class="flex flex-wrap gap-2 items-center">
                <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-xs px-3 py-1">
                    Dijalankan: <strong class="ml-1">{{ $runAt }}</strong>
                </span>
                <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-xs px-3 py-1">
                    Input: <strong class="ml-1">{{ $inputFrom }}</strong> s/d <strong>{{ $inputTo }}</strong>
                </span>
                <span class="inline-flex items-center gap-1 rounded-full bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-300 text-xs px-3 py-1">
                    Data aktual: <strong class="ml-1">{{ $dateFrom }}</strong> s/d <strong>{{ $dateTo }}</strong>
                </span>
                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 text-xs font-semibold px-3 py-1">
                    {{ $totalRules }} rules · {{ number_format($totalTx) }} transaksi
                </span>
            </div>
        </div>

        {{-- ── Stat bar ─────────────────────────────────────────────────────── --}}
        <div class="grid grid-cols-4 gap-4 mb-6">
            @foreach([
                ['label' => 'Total Rules',     'value' => $totalRules],
                ['label' => 'Total Transaksi', 'value' => number_format($totalTx)],
                ['label' => 'Min Support',     'value' => number_format($minSup * 100, 1) . '%'],
                ['label' => 'Min Confidence',  'value' => number_format($minConf * 100, 1) . '%'],
            ] as $s)
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-5 py-4">
                    <p class="text-xs text-gray-400 uppercase tracking-widest mb-1">{{ $s['label'] }}</p>
                    <p class="text-2xl font-bold text-gray-800 dark:text-white">{{ $s['value'] }}</p>
                </div>
            @endforeach
        </div>

        {{-- ══════════════════════════════════════════════════════════════════ --}}
        {{-- TOP 8 ASSOCIATION RULES TABLE                                      --}}
        {{-- ══════════════════════════════════════════════════════════════════ --}}
        @if(count($rules))
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 mb-6 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Top 8 Association Rules (Terurut)</h2>
                <p class="text-xs text-gray-400 mt-0.5">
                    Aturan terarah: "Jika memesan A maka memesan B" ≠ "Jika memesan B maka memesan A" — diurutkan berdasarkan Lift tertinggi
                </p>
            </div>
            <div class="overflow-x-auto">
                <table style="width:100%; border-collapse:collapse; font-size:0.78rem;">
                    <thead>
                        <tr style="background-color:#1d4ed8;">
                            <th style="padding:9px 12px; text-align:center; font-size:0.68rem; font-weight:700; color:#fff; text-transform:uppercase; letter-spacing:0.04em; border:1px solid #3b82f6; width:36px;">No</th>
                            <th style="padding:9px 12px; text-align:left;   font-size:0.68rem; font-weight:700; color:#fff; text-transform:uppercase; letter-spacing:0.04em; border:1px solid #3b82f6;">Pesan Pertama (A)</th>
                            <th style="padding:9px 12px; text-align:left;   font-size:0.68rem; font-weight:700; color:#fff; text-transform:uppercase; letter-spacing:0.04em; border:1px solid #3b82f6;">Pesan Berikutnya (B)</th>
                            <th style="padding:9px 12px; text-align:right;  font-size:0.68rem; font-weight:700; color:#fff; text-transform:uppercase; letter-spacing:0.04em; border:1px solid #3b82f6;">Jml A</th>
                            <th style="padding:9px 12px; text-align:right;  font-size:0.68rem; font-weight:700; color:#fff; text-transform:uppercase; letter-spacing:0.04em; border:1px solid #3b82f6;">Jml B</th>
                            <th style="padding:9px 12px; text-align:right;  font-size:0.68rem; font-weight:700; color:#fff; text-transform:uppercase; letter-spacing:0.04em; border:1px solid #3b82f6;">Jml A→B</th>
                            <th style="padding:9px 12px; text-align:right;  font-size:0.68rem; font-weight:700; color:#fff; text-transform:uppercase; letter-spacing:0.04em; border:1px solid #3b82f6;">Support</th>
                            <th style="padding:9px 12px; text-align:right;  font-size:0.68rem; font-weight:700; color:#fff; text-transform:uppercase; letter-spacing:0.04em; border:1px solid #3b82f6;">Confidence</th>
                            <th style="padding:9px 12px; text-align:right;  font-size:0.68rem; font-weight:700; color:#fff; text-transform:uppercase; letter-spacing:0.04em; border:1px solid #3b82f6;">Lift</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rules as $i => $rule)
                            @php
                                $liftStyle = $rule['lift'] >= 2
                                    ? 'color:#15803d; font-weight:700;'
                                    : ($rule['lift'] >= 1
                                        ? 'color:#b45309; font-weight:700;'
                                        : 'color:#dc2626; font-weight:700;');
                                $rowBg = $i % 2 === 0 ? 'background-color:#f8fafc;' : 'background-color:#ffffff;';
                            @endphp
                            <tr style="{{ $rowBg }}">
                                <td style="padding:9px 12px; text-align:center; color:#6b7280; font-size:0.72rem; border:1px solid #e2e8f0;">{{ $i + 1 }}</td>
                                <td style="padding:9px 12px; text-align:left; border:1px solid #e2e8f0;">
                                    <span style="display:inline-block; background-color:#fef08a; color:#713f12; padding:2px 8px; border-radius:4px; font-size:0.72rem; font-weight:600;">
                                        {{ $rule['menu_pertama'] }}
                                    </span>
                                </td>
                                <td style="padding:9px 12px; text-align:left; border:1px solid #e2e8f0;">
                                    <span style="display:inline-block; background-color:#dcfce7; color:#15803d; padding:2px 8px; border-radius:4px; font-size:0.72rem; font-weight:600;">
                                        {{ $rule['menu_kedua'] }}
                                    </span>
                                </td>
                                <td style="padding:9px 12px; text-align:right; color:#374151; font-size:0.72rem; border:1px solid #e2e8f0;">{{ number_format($rule['jumlah_menu_pertama']) }}</td>
                                <td style="padding:9px 12px; text-align:right; color:#374151; font-size:0.72rem; border:1px solid #e2e8f0;">{{ number_format($rule['jumlah_menu_kedua']) }}</td>
                                <td style="padding:9px 12px; text-align:right; color:#1d4ed8; font-weight:600; font-size:0.72rem; border:1px solid #e2e8f0;">{{ number_format($rule['jumlah_bersamaan']) }}</td>
                                <td style="padding:9px 12px; text-align:right; color:#374151; font-size:0.72rem; border:1px solid #e2e8f0;">{{ number_format($rule['support'] * 100, 2) }}%</td>
                                <td style="padding:9px 12px; text-align:right; color:#374151; font-size:0.72rem; border:1px solid #e2e8f0;">{{ number_format($rule['confidence'] * 100, 2) }}%</td>
                                <td style="padding:9px 12px; text-align:right; font-size:0.78rem; border:1px solid #e2e8f0; {{ $liftStyle }}">{{ number_format($rule['lift'], 2) }}</td>
                            </tr>
                            <tr style="background-color:#eff6ff;">
                                <td style="border:1px solid #e2e8f0;"></td>
                                <td colspan="8" style="padding:7px 12px; border:1px solid #e2e8f0;">
                                    <span style="font-size:0.72rem; color:#3b82f6; font-style:italic;">
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

        {{-- ══════════════════════════════════════════════════════════════════ --}}
        {{-- VISUALISASI Top Rules                                              --}}
        {{-- ══════════════════════════════════════════════════════════════════ --}}
        @if($chTopRules)
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 mb-6 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Visualisasi Top Rules Berdasarkan Lift</h2>
                <p class="text-xs text-gray-400 mt-0.5">Rule dengan nilai lift tertinggi menunjukkan asosiasi terkuat</p>
            </div>
            <div class="p-4">
                <img src="data:image/png;base64,{{ $chTopRules }}" alt="Top Rules Chart" class="w-full rounded"/>
            </div>
        </div>
        @endif

        {{-- ══════════════════════════════════════════════════════════════════ --}}
        {{-- VISUALISASI Frekuensi Kemunculan Tiap Menu                         --}}
        {{-- ══════════════════════════════════════════════════════════════════ --}}
        @if($chFreqItem)
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 mb-8 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Visualisasi Frekuensi Kemunculan Tiap Menu</h2>
                <p class="text-xs text-gray-400 mt-0.5">Frekuensi kemunculan tiap menu dalam transaksi</p>
            </div>
            <div class="p-4">
                <img src="data:image/png;base64,{{ $chFreqItem }}" alt="Frequent Itemsets Chart" class="w-full rounded"/>
            </div>
        </div>
        @endif

        {{-- ══════════════════════════════════════════════════════════════════ --}}
        {{-- TABEL FREQUENT 1-ITEMSETS                                          --}}
        {{-- ══════════════════════════════════════════════════════════════════ --}}
        @if(count($freq1))
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 mb-6 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Frequent 1-Itemsets</h2>
                <p class="text-xs text-gray-400 mt-0.5">Daftar menu yang sering muncul sendiri dalam transaksi</p>
            </div>
            <div class="overflow-x-auto">
                <table style="width:100%; border-collapse:collapse; font-size:0.76rem;">
                    <thead>
                        <tr style="background-color:#1e40af;">
                            <th style="padding:8px 12px; text-align:left;  font-size:0.67rem; font-weight:600; color:#fff; text-transform:uppercase; letter-spacing:0.04em; border:1px solid #3b82f6;">Menu</th>
                            <th style="padding:8px 12px; text-align:right; font-size:0.67rem; font-weight:600; color:#fff; text-transform:uppercase; letter-spacing:0.04em; border:1px solid #3b82f6;">Support</th>
                            <th style="padding:8px 12px; text-align:right; font-size:0.67rem; font-weight:600; color:#fff; text-transform:uppercase; letter-spacing:0.04em; border:1px solid #3b82f6;">Jml Transaksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($freq1 as $idx => $fi)
                        <tr style="{{ $idx % 2 === 0 ? 'background-color:#f8fafc;' : 'background-color:#ffffff;' }}">
                            <td style="padding:7px 12px; text-align:left;  color:#1d4ed8; font-weight:500; border:1px solid #e2e8f0;">{{ $fi['item'] }}</td>
                            <td style="padding:7px 12px; text-align:right; color:#374151; border:1px solid #e2e8f0;">{{ number_format($fi['support'] * 100, 2) }}%</td>
                            <td style="padding:7px 12px; text-align:right; color:#374151; font-weight:600; border:1px solid #e2e8f0;">{{ number_format($fi['jumlah_kemunculan']) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- ══════════════════════════════════════════════════════════════════ --}}
        {{-- TABEL FREQUENT 2-ITEMSETS                                          --}}
        {{-- ══════════════════════════════════════════════════════════════════ --}}
        @if(count($freq2))
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 mb-6 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Frequent 2-Itemsets (Terurut)</h2>
                <p class="text-xs text-gray-400 mt-0.5">Daftar 2 menu yang sering dipesan berurutan (A → B berarti A dipesan sebelum B)</p>
            </div>
            <div class="overflow-x-auto">
                <table style="width:100%; border-collapse:collapse; font-size:0.76rem;">
                    <thead>
                        <tr style="background-color:#1e40af;">
                            <th style="padding:8px 12px; text-align:left;  font-size:0.67rem; font-weight:600; color:#fff; text-transform:uppercase; letter-spacing:0.04em; border:1px solid #3b82f6;">Urutan Menu (A → B)</th>
                            <th style="padding:8px 12px; text-align:right; font-size:0.67rem; font-weight:600; color:#fff; text-transform:uppercase; letter-spacing:0.04em; border:1px solid #3b82f6;">Support</th>
                            <th style="padding:8px 12px; text-align:right; font-size:0.67rem; font-weight:600; color:#fff; text-transform:uppercase; letter-spacing:0.04em; border:1px solid #3b82f6;">Jml Transaksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($freq2 as $idx => $fi)
                        <tr style="{{ $idx % 2 === 0 ? 'background-color:#f8fafc;' : 'background-color:#ffffff;' }}">
                            <td style="padding:7px 12px; text-align:left;  color:#1d4ed8; font-weight:500; border:1px solid #e2e8f0;">{{ $fi['items'] }}</td>
                            <td style="padding:7px 12px; text-align:right; color:#374151; border:1px solid #e2e8f0;">{{ number_format($fi['support'] * 100, 2) }}%</td>
                            <td style="padding:7px 12px; text-align:right; color:#374151; font-weight:600; border:1px solid #e2e8f0;">{{ number_format($fi['jumlah_kemunculan']) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        @endforeach {{-- end foreach $results --}}

    @endif {{-- end hasResult --}}

</x-filament-panels::page>
