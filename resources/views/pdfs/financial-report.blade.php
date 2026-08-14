@extends('layouts.print')

@section('title', ($reportData->title ?? 'Laporan Keuangan') . ' — W9 Cafe')

@php
    use App\Helpers\AccountingFormatter;

    // ─── Determine column visibility ───
    $showDateCol = ($reportData->type === 'custom' || $reportData->aggregation === 'monthly');

    // ─── Determine if running total column should be shown ───
    $hasRunningTotal = false;
    foreach ($rows as $row) {
        if ($row->runningTotal !== null) {
            $hasRunningTotal = true;
            break;
        }
    }

    // ─── Group rows into sections for page break support ───
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
    // Don't forget the final section
    if (!empty($currentSectionRows)) {
        $sections[] = ['name' => $currentSectionName, 'rows' => $currentSectionRows];
    }

    // If no section delimiters, use single table
    if (!$hasSectionDelimiters) {
        $sections = [['name' => '', 'rows' => $rows]];
    }

    // ─── Aggregate type label ───
    $aggregationLabel = match($reportData->aggregation) {
        'monthly' => 'Bulanan',
        default   => 'Harian',
    };

    $typeLabel = match($reportData->type) {
        'simple' => 'Ringkasan',
        'rigid'  => 'Lengkap (Laba Rugi & Arus Kas)',
        'custom' => 'Kustom',
        default  => $reportData->type,
    };

    // ─── Column span for break rows ───
    $colSpan = ($showDateCol ? 1 : 0) + 1 + ($hasRunningTotal ? 1 : 0);
@endphp

@section('content')

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- Letterhead                                                   --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
<div class="letterhead">
    <div class="company">W9 Cafe</div>
    <div class="report-title">{{ $reportData->title ?? 'Laporan Keuangan' }}</div>
    <div class="meta">
        <span>Periode: {{ AccountingFormatter::dateIndoFull($reportData->dateStart) }} — {{ AccountingFormatter::dateIndoFull($reportData->dateEnd) }}</span>
        <span>|</span>
        <span>Agregasi: {{ $aggregationLabel }}</span>
        <span>|</span>
        <span>Tipe: {{ $typeLabel }}</span>
        <span>|</span>
        <span>Dicetak: {{ now()->format('d F Y, H:i') }} WIB</span>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- Summary Cards                                                --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
@if(!empty($summary))
<div class="summary-grid">
    @foreach($summary as $item)
    <div class="summary-card">
        <div class="summary-label">{{ $item->label }}</div>
        <div class="summary-value @if($item->isHighlighted) highlighted @endif">
            {{ $item->formattedValue }}
        </div>
    </div>
    @endforeach
</div>
@endif

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- Detail Tables (multi-section with page breaks)               --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
@if(!empty($rows))
    @php $isFirstSection = true; @endphp

    @foreach($sections as $section)
        <div @if(!$isFirstSection) class="section-break" @endif>

            {{-- Section header label --}}
            @if($hasSectionDelimiters && $section['name'] !== '')
            <div class="summary-section">
                <div class="section-title">{{ $section['name'] }}</div>
            </div>
            @endif

            <table class="financial-table">
                <thead>
                    <tr>
                        @if($showDateCol)
                            <th>Tanggal</th>
                        @endif
                        <th>Kategori</th>
                        <th class="amount">Jumlah (Rp)</th>
                        @if($hasRunningTotal)
                            <th class="amount">Saldo Berjalan (Rp)</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($section['rows'] as $row)
                        @php
                            // Determine row class based on type
                            $rowClasses = match($row->type) {
                                'Section'    => 'section-row',
                                'Total'      => 'total-row',
                                'GrandTotal' => 'grand-total',
                                default      => '',
                            };

                            // Add indentation class
                            if ($row->indentLevel > 0 && $row->indentLevel <= 3) {
                                $rowClasses .= ' indent-' . $row->indentLevel;
                            }

                            // Determine amount CSS class
                            $amountClass = 'amount';
                            if ($row->type === 'Income') {
                                $amountClass .= ' positive';
                            } elseif ($row->type === 'Expense') {
                                $amountClass .= ' negative';
                            }

                            // Running total class
                            $rtClass = 'amount';
                            if ($row->runningTotal !== null) {
                                $rtClass .= ($row->runningTotal >= 0) ? ' positive' : ' negative';
                            }
                        @endphp

                        <tr class="{{ trim($rowClasses) }}">
                            @if($showDateCol)
                                <td>{{ $row->date ? AccountingFormatter::dateIndo($row->date) : '' }}</td>
                            @endif

                            <td class="@if($row->isBold) bold-text @endif">
                                {{ $row->category }}
                            </td>

                            <td class="{{ $amountClass }}">
                                @if($row->amount != 0.0 || $row->isSection() && $row->amount == 0.0)
                                    {{ AccountingFormatter::rupiahAccounting($row->amount) }}
                                @endif
                            </td>

                            @if($hasRunningTotal)
                                <td class="{{ $rtClass }}">
                                    @if($row->runningTotal !== null)
                                        {{ AccountingFormatter::rupiahAccounting($row->runningTotal) }}
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @php $isFirstSection = false; @endphp
    @endforeach
@else
    {{-- Empty state --}}
    <table>
        <tbody>
            <tr class="empty-row">
                <td colspan="{{ max($colSpan, 2) }}">Tidak ada data transaksi pada periode ini.</td>
            </tr>
        </tbody>
    </table>
@endif

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- Footer with page number and generation timestamp            --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
<div class="footer">
    Dicetak pada {{ now()->format('d F Y H:i') }} WIB &mdash; W9 Cafe POS System
    <div class="page-info">Halaman {PAGE_NUM} dari {PAGE_COUNT}</div>
</div>

@overwrite
