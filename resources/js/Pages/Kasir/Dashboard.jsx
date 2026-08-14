import { useState, useEffect } from 'react';
import { router, Link, Head } from '@inertiajs/react';
import { Calendar, Plus, ClipboardList, Clock } from 'lucide-react';
import CashierLayout from '@/Layouts/CashierLayout';
import StatBar from '@/Components/Kasir/StatBar';
import StatusBadge from '@/Components/Common/StatusBadge';
import { formatRupiah, formatDate, formatTime } from '@/helpers';

export default function Dashboard({ totalPenjualan, jumlahTransaksi, pesananAktif, transaksiTerbaru }) {
    const [now, setNow] = useState(new Date());
    useEffect(() => {
        const timer = setInterval(() => setNow(new Date()), 1000);
        return () => clearInterval(timer);
    }, []);

    // Ambil data fresh saat halaman dibuka — hindari snapshot stale dari cache prefetch
    useEffect(() => {
        router.reload({ only: ['totalPenjualan', 'jumlahTransaksi', 'pesananAktif', 'cashPending', 'qrisPending', 'transaksiTerbaru'] });
    }, []);

    return (
        <><Head title="Dashboard | W9 Cafe" /><CashierLayout title="Dashboard" fullscreen>
            <div style={{ flex: 1, overflowY: 'auto', padding: 32, background: '#F8FAFC' }}>
            <div style={{ background: '#FFFFFF', borderRadius: 12, padding: 24, border: '1px solid #E2E8F0', boxShadow: '0 2px 8px rgba(15,23,42,0.03)' }}>

            {/* ── A. Header ── */}
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: 28 }}>
                <div>
                    <h1 style={{ fontSize: 26, fontWeight: 700, color: '#0F172A', margin: '0 0 4px', letterSpacing: '-0.5px' }}>
                        Dashboard
                    </h1>
                    <p style={{ fontSize: 14, color: '#64748B', margin: 0 }}>
                        Selamat datang, Kasir! Berikut ringkasan hari ini.
                    </p>
                </div>

                {/* Date chip */}
                <div style={{
                    display: 'flex',
                    alignItems: 'center',
                    gap: 8,
                    background: '#FFFFFF',
                    border: '1px solid #E2E8F0',
                    borderRadius: 8,
                    padding: '8px 14px',
                    fontSize: 13,
                    fontWeight: 500,
                    color: '#0F172A',
                    boxShadow: '0 2px 8px rgba(15,23,42,0.04)',
                    flexShrink: 0,
                }}>
                    <Calendar size={16} color="#64748B" />
                    {formatDate(now)}, {formatTime(now)}
                </div>
            </div>

            {/* ── B. Stat Bar ── */}
            <StatBar
                totalPenjualan={totalPenjualan}
                jumlahTransaksi={jumlahTransaksi}
                pesananAktif={pesananAktif}
            />

            {/* ── C. Quick Actions ── */}
            <div style={{ display: 'flex', gap: 12, marginBottom: 28 }}>
                <button
                    onClick={() => router.visit('/kasir/pesanan-baru')}
                    style={{
                        display: 'flex', alignItems: 'center', gap: 8,
                        height: 44, padding: '0 20px',
                        background: '#3B6FD4', color: 'white',
                        border: 'none', borderRadius: 8,
                        fontSize: 14, fontWeight: 600, cursor: 'pointer',
                        boxShadow: '0 4px 16px rgba(59,111,212,0.25)',
                    }}
                >
                    <Plus size={16} />
                    Pesanan Baru
                </button>

                <button
                    onClick={() => router.visit('/kasir/pesanan-aktif')}
                    style={{
                        display: 'flex', alignItems: 'center', gap: 8,
                        height: 44, padding: '0 20px',
                        background: '#FFFFFF', color: '#0F172A',
                        border: '1px solid #E2E8F0', borderRadius: 8,
                        fontSize: 14, fontWeight: 500, cursor: 'pointer',
                        boxShadow: '0 2px 8px rgba(15,23,42,0.04)',
                    }}
                >
                    <ClipboardList size={16} />
                    Lihat Pesanan
                </button>

                <button
                    onClick={() => router.visit('/kasir/riwayat-pesanan')}
                    style={{
                        display: 'flex', alignItems: 'center', gap: 8,
                        height: 44, padding: '0 20px',
                        background: '#FFFFFF', color: '#0F172A',
                        border: '1px solid #E2E8F0', borderRadius: 8,
                        fontSize: 14, fontWeight: 500, cursor: 'pointer',
                        boxShadow: '0 2px 8px rgba(15,23,42,0.04)',
                    }}
                >
                    <Clock size={16} />
                    Riwayat
                </button>
            </div>

            {/* ── D. Transaksi Terbaru ── */}
            <div>
                {/* Section header */}
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16 }}>
                    <h2 style={{ fontSize: 18, fontWeight: 600, color: '#0F172A', margin: 0, letterSpacing: '-0.2px' }}>
                        Transaksi Terbaru
                    </h2>
                    <Link
                        href="/kasir/riwayat-pesanan"
                        style={{ fontSize: 13, fontWeight: 500, color: '#3B6FD4', textDecoration: 'none' }}
                    >
                        Lihat Semua →
                    </Link>
                </div>

                {/* Table card */}
                <div style={{
                    background: '#FFFFFF',
                    border: '1px solid #E2E8F0',
                    borderRadius: 16,
                    overflow: 'hidden',
                    boxShadow: '0 4px 14px rgba(15,23,42,0.06)',
                }}>
                    <table style={{ width: '100%', borderCollapse: 'collapse' }}>
                        <thead>
                            <tr style={{ background: '#F1F5F9' }}>
                                {['ID Pesanan', 'Item', 'Total', 'Pembayaran', 'Status'].map((h, i) => (
                                    <th key={i} style={{
                                        padding: '12px 16px',
                                        fontSize: 12,
                                        fontWeight: 600,
                                        color: '#475569',
                                        textAlign: 'left',
                                        textTransform: 'uppercase',
                                        letterSpacing: '0.4px',
                                        borderBottom: '1px solid #E2E8F0',
                                        whiteSpace: 'nowrap',
                                    }}>
                                        {h}
                                    </th>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {transaksiTerbaru.length === 0 ? (
                                <tr>
                                    <td colSpan={5} style={{ padding: '32px 16px', textAlign: 'center', color: '#94A3B8', fontSize: 14 }}>
                                        Belum ada transaksi hari ini
                                    </td>
                                </tr>
                            ) : (
                                transaksiTerbaru.map((trx, i) => (
                                    <tr
                                        key={trx.id}
                                        style={{
                                            borderBottom: i < transaksiTerbaru.length - 1 ? '1px solid #E2E8F0' : 'none',
                                            transition: 'background 0.12s',
                                        }}
                                        onMouseEnter={e => e.currentTarget.style.background = '#F8FAFC'}
                                        onMouseLeave={e => e.currentTarget.style.background = 'transparent'}
                                    >
                                        <td style={{ padding: '14px 16px', fontSize: 13, fontWeight: 600, color: '#0F172A', whiteSpace: 'nowrap' }}>
                                            #{trx.order_code}
                                        </td>
                                        <td style={{ padding: '14px 16px', fontSize: 13, color: '#64748B', maxWidth: 400 }}>
                                            <span style={{ display: 'block', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                                                {trx.items_summary || '-'}
                                            </span>
                                        </td>
                                        <td style={{ padding: '14px 16px', fontSize: 13, fontWeight: 600, color: '#0F172A', whiteSpace: 'nowrap' }}>
                                            {formatRupiah(trx.total_amount)}
                                        </td>
                                        <td style={{ padding: '14px 16px', fontSize: 13, color: '#64748B', textTransform: 'capitalize' }}>
                                            {trx.payment_method || '-'}
                                        </td>
                                        <td style={{ padding: '14px 16px' }}>
                                            <StatusBadge status={trx.status} />
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            </div>

            </div>
            </div>
        </CashierLayout></>
    );
}
