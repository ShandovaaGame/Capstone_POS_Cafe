import { useState, useEffect } from 'react';
import { router, Head } from '@inertiajs/react';
import { ClipboardList, X } from 'lucide-react';
import PelangganLayout from '@/Layouts/PelangganLayout';
import RiwayatCard from '@/Components/Pelanggan/RiwayatCard';
import { formatRupiah, formatDate, formatTime } from '@/helpers';

const F  = '"Inter", system-ui, sans-serif';
const C  = {
    surface:    '#FFFFFF',
    bg:         '#F7F5F2',
    border:     '#E2DED8',
    accent:     '#44403C',
    accentDark: '#1C1917',
    textSecond: '#78716C',
    textMuted:  '#A8A29E',
    shadow:     '0 2px 8px -2px rgba(0,0,0,0.05)',
};

const TABS = [
    { key: 'all',      label: 'Semua'    },
    { key: 'pending',  label: 'Pending'  },
    { key: 'diproses', label: 'Diproses' },
    { key: 'selesai',  label: 'Selesai'  },
];

const METHOD_LABEL = { cash: 'Tunai', qris: 'QRIS' };

function groupByDate(orders, fmtDate) {
    const today = new Date(); today.setHours(0, 0, 0, 0);
    const yday  = new Date(today); yday.setDate(yday.getDate() - 1);
    const groups = new Map();
    orders.forEach(o => {
        const d = new Date(o.created_at); d.setHours(0, 0, 0, 0);
        const t = d.getTime();
        const key = t === today.getTime() ? 'HARI INI'
            : t === yday.getTime() ? 'KEMARIN'
            : fmtDate(o.created_at).toUpperCase();
        if (!groups.has(key)) groups.set(key, []);
        groups.get(key).push(o);
    });
    return groups;
}

export default function CustomerRiwayat({ orders = [] }) {
    const [activeTab,    setActiveTab]    = useState('all');
    const [receiptOrder, setReceiptOrder] = useState(null);

    const sessionName = (() => {
        try {
            const s = sessionStorage.getItem('w9_customer');
            return s ? JSON.parse(s)?.name ?? null : null;
        } catch (_) { return null; }
    })();

    useEffect(() => {
        const params = new URLSearchParams(window.location.search);
        if (!params.get('phone')) {
            try {
                const saved = sessionStorage.getItem('w9_customer');
                if (saved) {
                    const data = JSON.parse(saved);
                    if (data?.phone) {
                        router.visit(`/customer/riwayat?phone=${encodeURIComponent(data.phone)}`, {
                            preserveState: true, replace: true,
                        });
                    }
                }
            } catch (_) {}
        }
    }, []);

    useEffect(() => {
        const hasActive = orders.some(o => o.status !== 'selesai');
        if (!hasActive) return;
        const id = setInterval(() => {
            router.reload({ only: ['orders'], preserveState: true });
        }, 8000);
        return () => clearInterval(id);
    }, [orders]);

    const filteredOrders = orders.filter(o =>
        activeTab === 'all' ? true : o.status === activeTab
    );

    const grouped = groupByDate(filteredOrders, formatDate);

    return (
        <PelangganLayout activeTab="riwayat">
            <Head>
                <title>Riwayat Pesanan — W9 Cafe</title>
                <link rel="preconnect" href="https://fonts.googleapis.com" />
                <link rel="preconnect" href="https://fonts.gstatic.com" crossOrigin="anonymous" />
                <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" />
                <style>{`
                    html, body { background: #F7F5F2; }
                    .w9r-scroll::-webkit-scrollbar { display: none; }
                    .w9r-tab { transition: background 0.175s, color 0.175s, box-shadow 0.175s; }
                    .w9r-detail-btn:hover { background: #F7F5F2 !important; }
                `}</style>
            </Head>

            <div style={{
                position: 'fixed', top: 0, left: '50%', transform: 'translateX(-50%)',
                width: '100%', maxWidth: 430, height: '100vh',
                zIndex: 0, pointerEvents: 'none', overflow: 'hidden', background: C.bg,
            }}>
                <img src="/images/wallpaper-menu.jpg" alt=""
                    style={{ width: '100%', height: '100%', objectFit: 'cover', objectPosition: 'center top' }}
                />
            </div>

            <div style={{
                position: 'fixed', top: 0, left: '50%', transform: 'translateX(-50%)',
                width: '100%', maxWidth: 430, height: '100vh',
                display: 'flex', flexDirection: 'column', zIndex: 1,
            }}>

                <div className="w9r-scroll" style={{
                    flex: 1, overflowY: 'auto', scrollbarWidth: 'none',
                    WebkitOverflowScrolling: 'touch',
                    padding: '32px 24px 100px',
                    display: 'flex', flexDirection: 'column',
                }}>

                    <section style={{ marginBottom: 24, paddingLeft: 4 }}>
                        <h2 style={{
                            fontSize: 10, fontWeight: 700, color: C.accent,
                            textTransform: 'uppercase', letterSpacing: '0.12em',
                            fontFamily: F, marginBottom: 4,
                        }}>
                            TOTAL ORDER
                        </h2>
                        <p style={{
                            fontSize: 36, fontWeight: 700, color: C.accentDark,
                            fontFamily: F, letterSpacing: '-0.03em', margin: 0,
                        }}>
                            {orders.length}
                        </p>
                    </section>

                    <section style={{ marginBottom: 24 }}>
                        <div style={{ display: 'flex', gap: 6, alignItems: 'center', width: '100%' }}>
                            {TABS.map(tab => {
                                const active = activeTab === tab.key;
                                return (
                                    <button
                                        key={tab.key}
                                        onClick={() => setActiveTab(tab.key)}
                                        className="w9r-tab"
                                        style={{
                                            flex: 1,
                                            padding: active ? '8px 0' : '8px 0',
                                            borderRadius: 999, border: 'none', cursor: 'pointer',
                                            fontSize: 12, fontWeight: active ? 600 : 600,
                                            fontFamily: F,
                                            background: active
                                                ? C.accent
                                                : 'rgba(255,255,255,0.50)',
                                            backdropFilter: active ? 'none' : 'blur(4px)',
                                            color: active ? '#FFFFFF' : C.accent,
                                            boxShadow: active
                                                ? '0 4px 12px rgba(68,64,60,0.30)'
                                                : 'none',
                                            whiteSpace: 'nowrap',
                                        }}
                                    >
                                        {tab.label}
                                    </button>
                                );
                            })}
                        </div>
                    </section>

                    {filteredOrders.length === 0 ? (
                        <div style={{
                            display: 'flex', flexDirection: 'column',
                            alignItems: 'center', justifyContent: 'center',
                            padding: '60px 0', gap: 14,
                        }}>
                            <div style={{
                                width: 72, height: 72, borderRadius: 20,
                                background: 'rgba(255,255,255,0.80)',
                                border: `1px solid ${C.border}`,
                                display: 'flex', alignItems: 'center', justifyContent: 'center',
                            }}>
                                <ClipboardList size={30} color={C.textMuted} strokeWidth={1.5} />
                            </div>
                            <div style={{ textAlign: 'center' }}>
                                <p style={{ fontSize: 15, fontWeight: 600, color: C.accentDark, fontFamily: F, margin: '0 0 6px' }}>
                                    Belum ada pesanan
                                </p>
                                <p style={{ fontSize: 13, color: C.textSecond, fontFamily: F, margin: 0 }}>
                                    Pesanan kamu akan muncul di sini
                                </p>
                            </div>
                        </div>
                    ) : (
                        Array.from(grouped.entries()).map(([dateLabel, groupOrders]) => (
                            <div key={dateLabel}>
                                <div style={{ marginBottom: 12 }}>
                                    <span style={{
                                        fontSize: 11, fontWeight: 700, color: C.accent,
                                        textTransform: 'uppercase', letterSpacing: '0.10em',
                                        fontFamily: F,
                                        background: 'rgba(255,255,255,0.30)',
                                        backdropFilter: 'blur(4px)',
                                        padding: '2px 8px', borderRadius: 4,
                                    }}>
                                        {dateLabel}
                                    </span>
                                </div>

                                {groupOrders.map(order => (
                                    <RiwayatCard
                                        key={order.id}
                                        order={order}
                                        onDetail={setReceiptOrder}
                                    />
                                ))}
                            </div>
                        ))
                    )}

                </div>
            </div>

            {receiptOrder && (
                <div
                    onClick={() => setReceiptOrder(null)}
                    style={{
                        position: 'fixed', inset: 0,
                        background: 'rgba(28,25,23,0.60)',
                        backdropFilter: 'blur(4px)',
                        zIndex: 300,
                        display: 'flex', alignItems: 'center', justifyContent: 'center',
                        padding: '24px 20px',
                    }}
                >
                    <div
                        onClick={e => e.stopPropagation()}
                        style={{
                            background: C.surface,
                            borderRadius: 20,
                            width: '100%', maxWidth: 340,
                            maxHeight: 'calc(100vh - 48px)',
                            display: 'flex', flexDirection: 'column',
                            boxShadow: '0 20px 60px rgba(28,25,23,0.28)',
                            overflow: 'hidden',
                            fontFamily: F,
                        }}
                    >
                        <div style={{ height: 3, background: C.accent, flexShrink: 0 }} />

                        <div style={{ display: 'flex', justifyContent: 'flex-end', padding: '12px 16px 0', flexShrink: 0 }}>
                            <button
                                onClick={() => setReceiptOrder(null)}
                                style={{
                                    width: 32, height: 32, borderRadius: '50%',
                                    background: C.bg, border: `1px solid ${C.border}`,
                                    cursor: 'pointer',
                                    display: 'flex', alignItems: 'center', justifyContent: 'center',
                                }}
                            >
                                <X size={15} color={C.textSecond} />
                            </button>
                        </div>

                        <div style={{ overflowY: 'auto', flex: 1, padding: '4px 24px 8px' }}>

                            <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', gap: 8, paddingBottom: 18 }}>
                                <div style={{
                                    width: 54, height: 54, borderRadius: 14,
                                    overflow: 'hidden', background: '#1B3A4B',
                                    display: 'flex', alignItems: 'center', justifyContent: 'center',
                                    boxShadow: '0 4px 14px rgba(0,0,0,0.15)',
                                }}>
                                    <img
                                        src="/images/logo.jpg" alt="W9"
                                        style={{ width: '100%', height: '100%', objectFit: 'cover' }}
                                        onError={e => {
                                            e.target.style.display = 'none';
                                            e.target.parentElement.innerHTML = '<span style="color:white;font-size:18px;font-style:italic;font-weight:700">w9</span>';
                                        }}
                                    />
                                </div>
                                <span style={{ fontSize: 17, fontWeight: 700, color: C.accentDark, fontFamily: F, letterSpacing: '-0.02em' }}>
                                    W9 Cafe
                                </span>
                            </div>

                            <div style={{ height: 1, background: C.border, marginBottom: 16 }} />

                            <div style={{ display: 'flex', flexDirection: 'column', gap: 9, marginBottom: 16 }}>
                                {[
                                    { label: 'No. Pesanan', value: receiptOrder.order_code },
                                    { label: 'Tanggal',     value: `${formatDate(receiptOrder.created_at)}, ${formatTime(receiptOrder.created_at)}` },
                                    { label: 'Pelanggan',   value: receiptOrder.customer_name ?? sessionName ?? '—' },
                                    { label: 'Pembayaran',  value: METHOD_LABEL[receiptOrder.payment_method] ?? '—' },
                                ].map(row => (
                                    <div key={row.label} style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'baseline', gap: 12 }}>
                                        <span style={{ fontSize: 12, color: C.textSecond, flexShrink: 0, fontFamily: F }}>{row.label}</span>
                                        <span style={{ fontSize: 13, fontWeight: 600, color: C.accentDark, textAlign: 'right', fontFamily: F }}>{row.value}</span>
                                    </div>
                                ))}
                            </div>

                            <div style={{ height: 1, background: C.border, marginBottom: 14 }} />

                            <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 8 }}>
                                {['ITEM', 'QTY', 'HARGA'].map((h, i) => (
                                    <span key={h} style={{
                                        fontSize: 11, fontWeight: 700, color: C.textSecond,
                                        letterSpacing: '0.06em', fontFamily: F,
                                        flex: i === 0 ? 1 : undefined,
                                        width: i === 1 ? 32 : i === 2 ? 80 : undefined,
                                        textAlign: i === 0 ? 'left' : i === 1 ? 'center' : 'right',
                                    }}>{h}</span>
                                ))}
                            </div>

                            <div style={{ display: 'flex', flexDirection: 'column', gap: 8, marginBottom: 14 }}>
                                {(receiptOrder.items ?? []).map((item, i) => (
                                    <div key={i} style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                                        <span style={{ fontSize: 13, color: C.accentDark, flex: 1, fontFamily: F }}>{item.name}</span>
                                        <span style={{ fontSize: 13, color: C.textSecond, width: 32, textAlign: 'center', fontFamily: F }}>{item.quantity}×</span>
                                        <span style={{ fontSize: 13, color: C.accentDark, width: 80, textAlign: 'right', fontFamily: F }}>{formatRupiah(item.subtotal)}</span>
                                    </div>
                                ))}
                            </div>

                            <div style={{ height: 1, background: C.border, marginBottom: 12 }} />

                            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 8 }}>
                                <span style={{ fontSize: 13, color: C.textSecond, fontFamily: F }}>Subtotal</span>
                                <span style={{ fontSize: 13, color: C.textSecond, fontFamily: F }}>{formatRupiah(receiptOrder.total_amount)}</span>
                            </div>

                            <div style={{
                                display: 'flex', justifyContent: 'space-between', alignItems: 'center',
                                marginBottom: 20, padding: '10px 12px',
                                background: C.bg, borderRadius: 10, border: `1px solid ${C.border}`,
                            }}>
                                <span style={{ fontSize: 15, fontWeight: 700, color: C.accentDark, fontFamily: F }}>Total</span>
                                <span style={{ fontSize: 16, fontWeight: 700, color: C.accentDark, fontFamily: F, letterSpacing: '-0.02em' }}>
                                    {formatRupiah(receiptOrder.total_amount)}
                                </span>
                            </div>

                            <div style={{ textAlign: 'center', marginBottom: 18 }}>
                                <span style={{ fontSize: 13, fontWeight: 600, color: C.textSecond, fontFamily: F }}>
                                    Terima kasih sudah memesan!
                                </span>
                            </div>
                        </div>

                        <div style={{ padding: '10px 20px 22px', flexShrink: 0, borderTop: `1px solid ${C.border}` }}>
                            <button
                                onClick={() => setReceiptOrder(null)}
                                style={{
                                    width: '100%', height: 50,
                                    background: C.accent, color: '#FFFFFF',
                                    border: 'none', borderRadius: 12,
                                    fontSize: 15, fontWeight: 700, cursor: 'pointer',
                                    fontFamily: F,
                                    boxShadow: '0 4px 16px rgba(68,64,60,0.30)',
                                }}
                            >
                                Tutup
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </PelangganLayout>
    );
}
