<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $report_title }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.4;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #1A2332;
            padding-bottom: 15px;
        }
        .header h1 {
            font-size: 20px;
            color: #1A2332;
            margin-bottom: 5px;
        }
        .header .subtitle {
            font-size: 12px;
            color: #666;
        }
        .header .date-range {
            font-size: 11px;
            color: #3B6FD4;
            margin-top: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th, td {
            padding: 8px 10px;
            text-align: left;
            border: 1px solid #ddd;
        }
        th {
            background-color: #1A2332;
            color: white;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
        }
        .section-title {
            background-color: #3B6FD4;
            color: white;
            padding: 8px 12px;
            font-size: 12px;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 10px;
        }
        .summary-table th {
            background-color: #F8F9FA;
            color: #333;
        }
        .amount {
            text-align: right;
        }
        .positive {
            color: #28A745;
        }
        .negative {
            color: #DC3545;
        }
        .total-row {
            font-weight: bold;
            background-color: #F8F9FA;
        }
        .breakdown-row td:first-child {
            padding-left: 20px;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 9px;
            color: #999;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $report_title }}</h1>
        <div class="subtitle">W9 Cafe POS - Sistem Point of Sale</div>
        <div class="date-range">Periode: {{ $date_range }}</div>
    </div>

    @if($type === 'simple')
        <div class="section-title">Ringkasan Keuangan</div>
        <table class="summary-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th class="amount">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Total Pemasukan</td>
                    <td class="amount positive">{{ $total_income }}</td>
                </tr>
                <tr>
                    <td>Total Pengeluaran</td>
                    <td class="amount negative">{{ $total_expense }}</td>
                </tr>
                <tr class="total-row">
                    <td>Laba/Rugi Bersih</td>
                    <td class="amount {{ $net_positive ? 'positive' : 'negative' }}">{{ $net }}</td>
                </tr>
                <tr>
                    <td>Piutang Belum Terbayar</td>
                    <td class="amount">{{ $receivables_outstanding }}</td>
                </tr>
            </tbody>
        </table>

        <div class="section-title">Rincian Pemasukan</div>
        <table>
            <thead>
                <tr>
                    <th>Sumber</th>
                    <th class="amount">Jumlah</th>
                    <th class="amount">Jumlah Transaksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($income_breakdown as $item)
                <tr class="breakdown-row">
                    <td>{{ $item['source'] }}</td>
                    <td class="amount positive">{{ $item['total'] }}</td>
                    <td class="amount">{{ $item['count'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="section-title">Rincian Pengeluaran</div>
        <table>
            <thead>
                <tr>
                    <th>Kategori</th>
                    <th class="amount">Jumlah</th>
                    <th class="amount">Jumlah Transaksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($expense_breakdown as $item)
                <tr class="breakdown-row">
                    <td>{{ $item['source'] }}</td>
                    <td class="amount negative">{{ $item['total'] }}</td>
                    <td class="amount">{{ $item['count'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if($type === 'rigid')
        <div class="section-title">Laporan Laba/Rugi (Income Statement)</div>
        <table class="summary-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th class="amount">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Pendapatan</strong></td>
                    <td></td>
                </tr>
                <tr class="breakdown-row">
                    <td>- Pendapatan dari Pesanan</td>
                    <td class="amount">{{ $pendapatan_orders }}</td>
                </tr>
                <tr class="breakdown-row">
                    <td>- Pendapatan Tak Terduga</td>
                    <td class="amount">{{ $pendapatan_unexpected }}</td>
                </tr>
                <tr class="total-row">
                    <td>Total Pendapatan</td>
                    <td class="amount positive">{{ $pendapatan }}</td>
                </tr>
                <tr>
                    <td>Harga Pokok Penjualan (HPP)</td>
                    <td class="amount negative">{{ $hpp }}</td>
                </tr>
                <tr class="total-row">
                    <td>Laba Kotor</td>
                    <td class="amount {{ $laba_kotor_raw >= 0 ? 'positive' : 'negative' }}">{{ $laba_kotor }}</td>
                </tr>
                <tr>
                    <td>Beban Operasional</td>
                    <td class="amount negative">{{ $beban_operasional }}</td>
                </tr>
                <tr>
                    <td>Beban Tak Terduga</td>
                    <td class="amount negative">{{ $beban_tak_terduga }}</td>
                </tr>
                <tr class="total-row">
                    <td>Laba/Rugi Bersih</td>
                    <td class="amount {{ $laba_positive ? 'positive' : 'negative' }}">{{ $laba_rugi_bersih }}</td>
                </tr>
            </tbody>
        </table>

        <div class="page-break"></div>

        <div class="section-title">Laporan Arus Kas (Cash Flow)</div>
        <table class="summary-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th class="amount">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Arus Kas Masuk</strong></td>
                    <td></td>
                </tr>
                <tr class="breakdown-row">
                    <td>- Pendapatan</td>
                    <td class="amount">{{ $pendapatan }}</td>
                </tr>
                <tr class="breakdown-row">
                    <td>- Pembayaran Piutang</td>
                    <td class="amount">{{ $receivable_payments }}</td>
                </tr>
                <tr class="total-row">
                    <td>Total Arus Kas Masuk</td>
                    <td class="amount positive">{{ $arus_kas_masuk }}</td>
                </tr>
                <tr>
                    <td><strong>Arus Kas Keluar</strong></td>
                    <td></td>
                </tr>
                <tr class="breakdown-row">
                    <td>- Beban Operasional</td>
                    <td class="amount negative">{{ $beban_operasional }}</td>
                </tr>
                <tr class="breakdown-row">
                    <td>- Pembelian Bahan Baku</td>
                    <td class="amount negative">{{ $hpp }}</td>
                </tr>
                <tr class="breakdown-row">
                    <td>- Beban Tak Terduga</td>
                    <td class="amount negative">{{ $beban_tak_terduga }}</td>
                </tr>
                <tr class="total-row">
                    <td>Total Arus Kas Keluar</td>
                    <td class="amount negative">{{ $arus_kas_keluar }}</td>
                </tr>
                <tr class="total-row">
                    <td>Saldo Awal</td>
                    <td class="amount">{{ $saldo_awal }}</td>
                </tr>
                <tr class="total-row">
                    <td>Arus Kas Bersih</td>
                    <td class="amount {{ $arus_kas_positive ? 'positive' : 'negative' }}">{{ $arus_kas_bersih }}</td>
                </tr>
                <tr class="total-row">
                    <td>Saldo Akhir</td>
                    <td class="amount {{ $arus_kas_positive ? 'positive' : 'negative' }}">{{ $saldo_akhir }}</td>
                </tr>
            </tbody>
        </table>
    @endif

    @if($type === 'custom')
        <div class="section-title">Detail Transaksi ({{ $aggregation === 'daily' ? 'Harian' : 'Bulanan' }})</div>
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Kategori</th>
                    <th>Tipe</th>
                    <th class="amount">Jumlah</th>
                    <th class="amount">Total Berjalan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                <tr>
                    <td>{{ $row['date'] }}</td>
                    <td>{{ $row['category'] }}</td>
                    <td>{{ $row['type_label'] }}</td>
                    <td class="amount {{ $row['type'] === 'Income' ? 'positive' : 'negative' }}">{{ $row['amount'] }}</td>
                    <td class="amount {{ $row['running_total_raw'] >= 0 ? 'positive' : 'negative' }}">{{ $row['running_total'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="section-title">Ringkasan</div>
        <table class="summary-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th class="amount">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Total Pemasukan</td>
                    <td class="amount positive">{{ $total_income }}</td>
                </tr>
                <tr>
                    <td>Total Pengeluaran</td>
                    <td class="amount negative">{{ $total_expense }}</td>
                </tr>
                <tr class="total-row">
                    <td>Saldo Bersih</td>
                    <td class="amount {{ $net_positive ? 'positive' : 'negative' }}">{{ $net }}</td>
                </tr>
            </tbody>
        </table>
    @endif

    <div class="footer">
        Generated by W9 Cafe POS | Tanggal: {{ $generated_at }}
    </div>
</body>
</html>