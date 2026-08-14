import { formatRupiah } from '@/helpers';

const metrics = (totalPenjualan, jumlahTransaksi, pesananAktif) => [
    { value: formatRupiah(totalPenjualan), label: 'Total Penjualan Hari Ini' },
    { value: jumlahTransaksi,             label: 'Jumlah Transaksi' },
    { value: pesananAktif,                label: 'Pesanan Aktif' },
];

export default function StatBar({ totalPenjualan, jumlahTransaksi, pesananAktif }) {
    return (
        <div style={{ display: 'flex', gap: 20, marginBottom: 24 }}>
            {metrics(totalPenjualan, jumlahTransaksi, pesananAktif).map((m, i) => (
                <div key={i} style={{
                    flex: 1,
                    background: '#FFFFFF',
                    border: '1px solid #E2E8F0',
                    borderRadius: 16,
                    padding: 20,
                    display: 'flex',
                    flexDirection: 'column',
                    gap: 8,
                    boxShadow: '0 4px 14px rgba(15,23,42,0.06)',
                }}>
                    <div style={{
                        fontSize: 28,
                        fontWeight: 700,
                        color: '#0F172A',
                        letterSpacing: '-1px',
                        lineHeight: 1,
                    }}>
                        {m.value}
                    </div>
                    <div style={{ fontSize: 13, fontWeight: 500, color: '#64748B' }}>
                        {m.label}
                    </div>
                </div>
            ))}
        </div>
    );
}
