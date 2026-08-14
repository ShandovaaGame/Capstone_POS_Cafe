{{-- Report Tables --}}
@php
    $reportData = $this->getReportData();
    $rows = $reportData->rows;

    // Determine column visibility
    $hasDateCol = false;
    $hasRunningTotal = false;
    foreach ($rows as $row) {
        if ($row->date) { $hasDateCol = true; }
        if ($row->runningTotal !== null) { $hasRunningTotal = true; }
    }

    // Group rows into sections
    $sections = [];
    $currentSectionName = '';
    $currentSectionRows = [];
    $hasSectionDelimiters = false;

    foreach ($rows as $row) {
        if ($row->isSection()) {
            $hasSectionDelimiters = true;
            if (!empty($currentSectionRows)) {
                $sections[] = ['name' => $currentSectionName, 'rows' => $currentSectionRows];
            }
            $currentSectionName = $row->category;
            $currentSectionRows = [];
        }
        $currentSectionRows[] = $row;
    }
    if (!empty($currentSectionRows)) {
        $sections[] = ['name' => $currentSectionName, 'rows' => $currentSectionRows];
    }

    // If no section delimiters, use single flat table
    if (!$hasSectionDelimiters) {
        $sections = [['name' => '', 'rows' => $rows]];
    }
@endphp

@forelse ($sections as $section)
    {{-- Section header --}}
    @if ($section['name'] !== '')
        <div class="text-base font-semibold text-gray-900 dark:text-gray-100 px-1 pt-2 pb-1">
            {{ $section['name'] }}
        </div>
    @endif

    {{-- Table --}}
    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                    @if($hasDateCol)
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tanggal</th>
                    @endif
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kategori</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Jumlah (Rp)</th>
                    @if($hasRunningTotal)
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Saldo Berjalan</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                @foreach ($section['rows'] as $row)
                    @php
                        $isSection = $row->isSection();
                        if ($isSection) { continue; }

                        $isTotal = $row->isTotal();
                        $isGrandTotal = $row->isGrandTotal();

                        if ($isGrandTotal) {
                            $rowStyle = 'font-bold text-gray-900 dark:text-gray-100 border-t-2 border-gray-300 dark:border-gray-600';
                        } elseif ($isTotal) {
                            $rowStyle = 'font-semibold text-gray-800 dark:text-gray-200 border-t border-gray-200 dark:border-gray-700';
                        } elseif ($row->isBold) {
                            $rowStyle = 'font-bold text-gray-900 dark:text-gray-100';
                        } else {
                            $rowStyle = 'text-gray-700 dark:text-gray-300';
                        }

                        $paddingLeft = 16 + $row->indentLevel * 20;
                    @endphp
                    <tr class="{{ $rowStyle }}">
                        @if($hasDateCol)
                            <td class="px-4 py-2.5 whitespace-nowrap">
                                {{ $row->date ? \Carbon\Carbon::parse($row->date)->format('d M Y') : '' }}
                            </td>
                        @endif
                        <td class="px-4 py-2.5" style="padding-left: {{ $paddingLeft }}px">
                            {{ $row->category }}
                        </td>
                        <td class="px-4 py-2.5 text-right whitespace-nowrap tabular-nums font-medium">
                            {{ $row->amount != 0 ? 'Rp ' . number_format($row->amount, 0, ',', '.') : '' }}
                        </td>
                        @if($hasRunningTotal)
                            <td class="px-4 py-2.5 text-right whitespace-nowrap tabular-nums">
                                {{ $row->runningTotal !== null ? 'Rp ' . number_format($row->runningTotal, 0, ',', '.') : '' }}
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@empty
    <div class="text-center py-12 text-gray-500 dark:text-gray-400">
        Tidak ada data transaksi pada periode ini.
    </div>
@endforelse
