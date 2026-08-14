import { useEffect } from 'react';
import { router } from '@inertiajs/react';
import PelangganLayout from '@/Layouts/PelangganLayout';
import { formatRupiah, formatDate, formatTime } from '@/helpers';

const STATUS_CONFIG = {
    pending:    { icon: '⏳', label: 'Menunggu Pembayaran', color: '#D4A04A', bg: '#FDF6E8' },
    confirmed:  { icon: '✓',  label: 'Pesanan Dikonfirmasi', color: '#5A9A6E', bg: '#EDF7F0' },
    preparing:  { icon: '👨‍🍳', label: 'Sedang Diproses',    color: '#4A7EC9', bg: '#EBF2FB' },
    ready:      { icon: '🛎', label: 'Siap Diambil',        color: '#5A9A6E', bg: '#EDF7F0' },
    completed:  { icon: '✅', label: 'Selesai',             color: '#5A9A6E', bg: '#EDF7F0' },
    cancelled:  { icon: '✕',  label: 'Dibatalkan',          color: '#C0544A', bg: '#FDEDEC' },
};

export default function OrderStatus({ order }) {
    const cfg = STATUS_CONFIG[order.status] ?? STATUS_CONFIG.pending;

    useEffect(() => {
        if (['completed', 'cancelled'].includes(order.status)) return;
        const id = setInterval(() => {
            router.reload({ only: ['order'] });
        }, 10_000);
        return () => clearInterval(id);
    }, [order.status]);

    return (
        <PelangganLayout activeTab="riwayat">
            <div style={{
                display: 'flex', flexDirection: 'column',
                gap: 16, padding: '24px 24px 24px',
            }}>

                <div style={{
                    background: cfg.bg, borderRadius: 20,
                    padding: '28px 24px', textAlign: 'center',
                    border: `1px solid ${cfg.color}30`,
                }}>
                    <div style={{ fontSize: 44, marginBottom: 12 }}>{cfg.icon}</div>
                    <div style={{
                        fontSize: 18, fontWeight: 700, color: cfg.color,
                        fontFamily: '"DM Sans", system-ui, sans-serif',
                        marginBottom: 4,
                    }}>
                        {cfg.label}
                    </div>
                    <div style={{ fontSize: 13, color: '#8C7B6B', fontFamily: 'Outfit, system-ui, sans-serif' }}>
                        #{order.order_code}
                    </div>
                </div>

                <div style={{
                    background: '#FFFFFF', borderRadius: 20,
                    border: '1px solid #EDE8E2', padding: 20,
                    boxShadow: '0 4px 14px rgba(45,32,22,0.06)',
                    display: 'flex', flexDirection: 'column', gap: 14,
                }}>
                    <span style={{ fontSize: 15, fontWeight: 700, color: '#2D2016', fontFamily: '"DM Sans", system-ui, sans-serif' }}>
                        Detail Pesanan
                    </span>

                    {[
                        ['Tanggal', `${formatTime(order.created_at)} - ${formatDate(order.created_at)}`],
                        ['Item',    order.items_summary],
                        ['Metode',  order.payment_method ? order.payment_method.toUpperCase() : '-'],
                        ['Total',   formatRupiah(order.total_amount)],
                    ].map(([label, value]) => (
                        <div key={label} style={{
                            display: 'flex', justifyContent: 'space-between',
                            paddingBottom: 12, borderBottom: '1px solid #F5F0EB',
                        }}>
                            <span style={{ fontSize: 13, color: '#B5A898', fontFamily: 'Outfit, system-ui, sans-serif' }}>
                                {label}
                            </span>
                            <span style={{
                                fontSize: 13, fontWeight: 600, color: '#2D2016',
                                fontFamily: 'Outfit, system-ui, sans-serif',
                                maxWidth: '55%', textAlign: 'right',
                            }}>
                                {value}
                            </span>
                        </div>
                    ))}
                </div>

                <button
                    onClick={() => router.visit('/pelanggan/menu')}
                    style={{
                        background: '#E8763A', color: '#FFFFFF',
                        border: 'none', borderRadius: 50,
                        height: 52, width: '100%',
                        fontSize: 15, fontWeight: 600,
                        fontFamily: 'Outfit, system-ui, sans-serif',
                        cursor: 'pointer',
                        boxShadow: '0 2px 8px rgba(232,118,58,0.25)',
                    }}
                >
                    Pesan Lagi
                </button>

            </div>
        </PelangganLayout>
    );
}
