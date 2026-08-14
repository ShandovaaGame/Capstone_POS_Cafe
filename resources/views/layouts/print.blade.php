<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>@yield('title', 'W9 Cafe — Financial Report')</title>
    <style>
        /* ── DomPDF-compatible print styles ── */
        /* NO flexbox, NO grid, NO position:fixed/sticky */
        /* NO external fonts, NO JavaScript */

        @page {
            size: A4 landscape;
            margin: 20mm 25mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', 'Helvetica', 'Arial', sans-serif;
            font-size: 9pt;
            color: #1A1A2E;
            line-height: 1.5;
            background: #ffffff;
        }

        /* ── Letterhead / Header ── */
        .letterhead {
            text-align: center;
            margin-bottom: 20pt;
            border-bottom: 2pt solid #1A2332;
            padding-bottom: 12pt;
        }

        .letterhead .company {
            font-size: 16pt;
            font-weight: bold;
            color: #1A2332;
            text-transform: uppercase;
            letter-spacing: 2pt;
        }

        .letterhead .report-title {
            font-size: 11pt;
            font-weight: bold;
            color: #3B6FD4;
            margin-top: 4pt;
        }

        .letterhead .meta {
            font-size: 8pt;
            color: #6C757D;
            margin-top: 4pt;
        }

        .letterhead .meta span {
            margin: 0 8pt;
        }

        /* ── Summary Cards (inline-block for DomPDF compatibility) ── */
        .summary-grid {
            text-align: center;
            margin-bottom: 18pt;
            font-size: 0; /* remove inline-block whitespace gap */
        }

        .summary-card {
            display: inline-block;
            vertical-align: top;
            border: 1pt solid #DDDDDD;
            border-radius: 4pt;
            padding: 8pt 12pt;
            margin: 0 4pt 8pt 4pt;
            min-width: 130pt;
            font-size: 9pt; /* restore font size after parent reset */
            text-align: left;
        }

        .summary-card .summary-label {
            font-size: 7.5pt;
            color: #6C757D;
            text-transform: uppercase;
            letter-spacing: 0.5pt;
            margin-bottom: 3pt;
        }

        .summary-card .summary-value {
            font-size: 13pt;
            font-weight: bold;
            color: #1A2332;
            font-family: 'DejaVu Sans Mono', monospace;
        }

        .summary-card .summary-value.highlighted {
            color: #3B6FD4;
        }

        /* ── Tables ── */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
            margin-bottom: 16pt;
        }

        thead {
            display: table-header-group;
        }

        tbody {
            display: table-row-group;
        }

        tr {
            page-break-inside: avoid;
        }

        th {
            background-color: #1A2332;
            color: #ffffff;
            padding: 6pt 8pt;
            text-align: left;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8pt;
            letter-spacing: 0.5pt;
        }

        th.amount {
            text-align: right;
        }

        td {
            padding: 5pt 8pt;
            border-bottom: 1pt solid #E9ECEF;
            vertical-align: top;
        }

        td.amount {
            text-align: right;
            font-family: 'DejaVu Sans Mono', monospace;
            white-space: nowrap;
        }

        /* ── Row styling ── */
        .section-row td {
            background-color: #EEF2FF;
            font-weight: 600;
            font-size: 9.5pt;
            color: #1A2332;
            border-bottom: 1.5pt solid #3B6FD4;
        }

        .total-row td {
            font-weight: bold;
            border-top: 2pt solid #9CA3AF;
        }

        .grand-total td {
            font-weight: bold;
            border-top: 3pt double #374151;
            background-color: #F8F9FA;
            font-size: 10pt;
        }

        /* ── Bold text utility ── */
        .bold-text {
            font-weight: bold;
        }

        /* ── Indentation levels for hierarchical rows ── */
        .indent-1 td:first-child {
            padding-left: 16pt;
        }
        .indent-2 td:first-child {
            padding-left: 28pt;
        }
        .indent-3 td:first-child {
            padding-left: 40pt;
        }

        /* ── Summary table fallback ── */
        .summary-section {
            margin-bottom: 20pt;
        }

        .summary-section .section-title {
            font-size: 11pt;
            font-weight: bold;
            color: #1A2332;
            margin-bottom: 8pt;
            padding-bottom: 4pt;
            border-bottom: 1pt solid #E9ECEF;
        }

        .summary-list {
            width: 100%;
            border-collapse: collapse;
        }

        .summary-list td {
            padding: 4pt 8pt;
            border-bottom: none;
            font-size: 9pt;
        }

        .summary-list td.label {
            text-align: left;
            width: 60%;
        }

        .summary-list td.value {
            text-align: right;
            font-family: 'DejaVu Sans Mono', monospace;
            font-weight: 600;
            white-space: nowrap;
        }

        .summary-list tr.highlight td {
            font-weight: bold;
            font-size: 10pt;
            color: #1A2332;
        }

        /* ── Footer ── */
        .footer {
            margin-top: 24pt;
            text-align: center;
            font-size: 7.5pt;
            color: #6C757D;
            border-top: 1pt solid #E9ECEF;
            padding-top: 8pt;
        }

        .footer .page-info {
            font-size: 7pt;
            color: #9AA3AF;
        }

        /* ── Page break utility ── */
        .page-break {
            page-break-before: always;
        }

        .section-break {
            page-break-before: always;
        }

        /* ── Positive / Negative colors ── */
        .positive {
            color: #28A745;
        }

        .negative {
            color: #DC3545;
        }

        /* ── Empty row placeholder ── */
        .empty-row td {
            color: #9AA3AF;
            font-style: italic;
            text-align: center;
            padding: 16pt 8pt;
        }
    </style>
    @yield('styles')
</head>
<body>
    @yield('content')
</body>
</html>
