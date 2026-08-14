import { useState } from 'react';
import { router, Head } from '@inertiajs/react';
import axios from 'axios';
import useCart from '@/Hooks/useCart';
import { ChevronLeft, Banknote, QrCode, MapPin, Wallet } from 'lucide-react';
import PelangganLayout from '@/Layouts/PelangganLayout';
import { formatRupiah } from '@/helpers';

const F = '"Inter", system-ui, sans-serif';

const C = {
    surface:    '#FFFFFF',
    bg:         '#F7F5F2',
    border:     '#F3F4F6',
    borderMd:   '#E5E7EB',
    accent:     '#44403C',
    accentDark: '#1C1917',
    textHead:   '#1C1917',
    textSecond: '#78716C',
    shadow:     '0 2px 8px -2px rgba(0,0,0,0.05)',
};

const METHODS = [
    {
        key:   'cash',
        title: 'Bayar ke Kasir (Cash)',
        desc:  'Bayar langsung di kasir setelah pesanan dikonfirmasi',
        Icon:  Banknote,
    },
    {
        key:   'qris',
        title: 'QRIS',
        desc:  'Scan QR code & upload bukti pembayaran',
        Icon:  QrCode,
    },
];

export default function PaymentChoose({ order, items, table_number }) {
    const [selected,      setSelected]      = useState(null);
    const [loading,       setLoading]       = useState(false);
    const [error,         setError]         = useState('');
    const [showCashModal, setShowCashModal] = useState(false);
    const [cashOrderCode, setCashOrderCode] = useState('');
    const { clearCart } = useCart();

    async function handleLanjut() {
        if (!selected || loading) return;
        setLoading(true);
        setError('');
        try {
            if (selected === 'cash') {
                const res = await axios.post(`/api/order/${order.id}/pay/cash`);
                setCashOrderCode(res.data.order_code ?? '');
                clearCart();
                setShowCashModal(true);
            } else {
                await axios.post(`/api/order/${order.id}/pay/qris`);
                router.visit(`/pelanggan/bayar/${order.order_code}/qris`);
            }
        } catch (err) {
            setError(err.response?.data?.message ?? 'Terjadi kesalahan. Coba lagi.');
        } finally {
            setLoading(false);
        }
    }

    function handleMengerti() {
        let tableId = null;
        try {
            const saved = sessionStorage.getItem('w9_customer');
            if (saved) tableId = JSON.parse(saved)?.tableId;
        } catch (_) {}
        router.visit(tableId ? `/pelanggan/menu?table=${tableId}` : '/pelanggan/menu');
    }

    return (
        <PelangganLayout activeTab="cart">
            <Head>
                <title>Pilih Pembayaran — W9 Cafe</title>
                <link rel="preconnect" href="https://fonts.googleapis.com" />
                <link rel="preconnect" href="https://fonts.gstatic.com" crossOrigin="anonymous" />
                <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" />
                <style>{`
                    html, body { background: #F7F5F2; }
                    .w9p-scroll::-webkit-scrollbar { display: none; }
                    .w9p-btn-back { transition: background 0.15s; }
                    .w9p-btn-back:hover { background: #F3F4F6 !important; }
                    .w9p-method { transition: border-color 0.2s, box-shadow 0.2s; }
                    .w9p-confirm {
                        transition: background 0.15s, transform 0.1s;
                    }
                    .w9p-confirm:active { transform: scale(0.98); }
                    .w9p-confirm:hover:not(:disabled) { background: ${C.accentDark} !important; }
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
                background: 'rgba(247,245,242,0.60)',
                backdropFilter: 'blur(2px)',
            }}>

                <header style={{
                    padding: '32px 24px 16px',
                    display: 'flex', alignItems: 'flex-start', gap: 16,
                    flexShrink: 0,
                }}>
                    <button
                        onClick={() => router.visit('/pelanggan/keranjang')}
                        className="w9p-btn-back"
                        style={{
                            marginTop: 2,
                            width: 36, height: 36, borderRadius: '50%',
                            background: C.surface,
                            border: `1px solid ${C.border}`,
                            boxShadow: C.shadow,
                            cursor: 'pointer',
                            display: 'flex', alignItems: 'center', justifyContent: 'center',
                            flexShrink: 0,
                        }}
                    >
                        <ChevronLeft size={20} color={C.accent} strokeWidth={2} />
                    </button>

                    <div style={{ flex: 1 }}>
                        <h1 style={{
                            fontSize: 20, fontWeight: 700, color: C.textHead,
                            fontFamily: F, letterSpacing: '-0.02em', margin: 0,
                        }}>
                            Pilih Cara Bayar
                        </h1>
                        <p style={{
                            fontSize: 12, fontWeight: 500, color: C.textSecond,
                            fontFamily: F, marginTop: 4,
                        }}>
                            Pesanan #{order.order_code}
                        </p>
                        {table_number && (
                            <div style={{ display: 'flex', alignItems: 'center', gap: 4, marginTop: 4 }}>
                                <MapPin size={12} color={C.textSecond} strokeWidth={2} />
                                <span style={{ fontSize: 12, color: C.textSecond, fontFamily: F }}>
                                    Meja {table_number}
                                </span>
                            </div>
                        )}
                    </div>
                </header>

                <div className="w9p-scroll" style={{
                    flex: 1, overflowY: 'auto',
                    scrollbarWidth: 'none',
                    WebkitOverflowScrolling: 'touch',
                    padding: '0 0 24px',
                    display: 'flex', flexDirection: 'column',
                }}>

                    <section style={{ padding: '0 24px', marginTop: 8 }}>
                        <h2 style={{
                            fontSize: 10, fontWeight: 700, color: C.textSecond,
                            textTransform: 'uppercase', letterSpacing: '0.12em',
                            fontFamily: F, marginBottom: 12,
                        }}>
                            Ringkasan Pesanan
                        </h2>

                        <div style={{
                            background: C.surface,
                            borderRadius: 12,
                            border: `1px solid ${C.border}`,
                            boxShadow: C.shadow,
                            overflow: 'hidden',
                        }}>
                            {items.map((item, idx) => (
                                <div key={idx} style={{
                                    padding: '14px 16px',
                                    display: 'flex', alignItems: 'center', justifyContent: 'space-between',
                                    borderBottom: idx < items.length - 1 ? `1px solid ${C.border}` : 'none',
                                }}>
                                    <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
                                        <span style={{
                                            background: C.bg,
                                            color: C.accent,
                                            fontSize: 10, fontWeight: 700,
                                            padding: '3px 7px', borderRadius: 4,
                                            fontFamily: F, flexShrink: 0,
                                        }}>
                                            {item.qty}x
                                        </span>
                                        <span style={{ fontSize: 14, fontWeight: 500, color: C.textHead, fontFamily: F }}>
                                            {item.name}
                                        </span>
                                    </div>
                                    <span style={{ fontSize: 14, fontWeight: 600, color: C.textHead, fontFamily: F }}>
                                        {formatRupiah(item.subtotal)}
                                    </span>
                                </div>
                            ))}

                            <div style={{
                                padding: '14px 16px',
                                display: 'flex', alignItems: 'center', justifyContent: 'space-between',
                                borderTop: `1px solid ${C.border}`,
                                background: 'rgba(247,245,242,0.5)',
                            }}>
                                <span style={{ fontSize: 15, fontWeight: 700, color: C.textHead, fontFamily: F }}>
                                    Total
                                </span>
                                <span style={{ fontSize: 18, fontWeight: 700, color: C.textHead, fontFamily: F }}>
                                    {formatRupiah(order.total_amount)}
                                </span>
                            </div>
                        </div>
                    </section>

                    <section style={{ padding: '0 24px', marginTop: 24 }}>
                        <h2 style={{
                            fontSize: 10, fontWeight: 700, color: C.textSecond,
                            textTransform: 'uppercase', letterSpacing: '0.12em',
                            fontFamily: F, marginBottom: 12,
                        }}>
                            Metode Pembayaran
                        </h2>

                        <div style={{ display: 'flex', flexDirection: 'column', gap: 10 }}>
                            {METHODS.map(({ key, title, desc, Icon }) => {
                                const active = selected === key;
                                return (
                                    <div
                                        key={key}
                                        onClick={() => setSelected(key)}
                                        className="w9p-method"
                                        style={{
                                            background: C.surface,
                                            borderRadius: 12,
                                            border: `1px solid ${active ? C.accent : C.border}`,
                                            boxShadow: active
                                                ? `0 0 0 1px ${C.accent}, ${C.shadow}`
                                                : C.shadow,
                                            padding: '14px 16px',
                                            display: 'flex', alignItems: 'center', gap: 14,
                                            cursor: 'pointer',
                                        }}
                                    >
                                        <div style={{
                                            width: 40, height: 40, borderRadius: 10,
                                            background: C.bg,
                                            display: 'flex', alignItems: 'center', justifyContent: 'center',
                                            flexShrink: 0,
                                        }}>
                                            {key === 'qris'
                                                ? <img src="/images/logo-qris.png" alt="QRIS"
                                                    style={{ width: 24, height: 24, objectFit: 'contain' }} />
                                                : <Icon size={20} color={C.accent} strokeWidth={2} />
                                            }
                                        </div>

                                        <div style={{ flex: 1 }}>
                                            <p style={{
                                                fontSize: 14, fontWeight: 700, color: C.textHead,
                                                fontFamily: F, margin: 0, lineHeight: 1,
                                            }}>
                                                {title}
                                            </p>
                                            <p style={{
                                                fontSize: 11, color: C.textSecond,
                                                fontFamily: F, marginTop: 5, lineHeight: 1.4,
                                            }}>
                                                {desc}
                                            </p>
                                        </div>

                                        <div style={{
                                            width: 20, height: 20, borderRadius: '50%',
                                            border: `2px solid ${active ? C.accent : C.borderMd}`,
                                            display: 'flex', alignItems: 'center', justifyContent: 'center',
                                            flexShrink: 0,
                                            transition: 'border-color 0.2s',
                                        }}>
                                            <div style={{
                                                width: 10, height: 10, borderRadius: '50%',
                                                background: C.accent,
                                                opacity: active ? 1 : 0,
                                                transition: 'opacity 0.2s',
                                            }} />
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </section>

                </div>

                <div style={{
                    padding: '12px 24px 84px',
                    flexShrink: 0,
                    background: 'transparent',
                }}>
                    {error && (
                        <div style={{
                            marginBottom: 10,
                            background: '#FEF2F2', border: '1px solid #FECACA',
                            borderRadius: 10, padding: '10px 14px',
                            fontSize: 13, color: '#DC2626', fontFamily: F,
                        }}>
                            {error}
                        </div>
                    )}
                    <button
                        onClick={handleLanjut}
                        disabled={!selected || loading}
                        className="w9p-confirm"
                        style={{
                            width: '100%',
                            padding: '16px 0',
                            background: !selected || loading ? '#D6D3D1' : C.accent,
                            color: '#FFFFFF',
                            border: 'none', borderRadius: 12,
                            fontSize: 15, fontWeight: 700,
                            cursor: !selected || loading ? 'not-allowed' : 'pointer',
                            boxShadow: !selected || loading
                                ? 'none'
                                : '0 4px 16px rgba(68,64,60,0.30)',
                            fontFamily: F, letterSpacing: '-0.01em',
                        }}
                    >
                        {loading ? 'Memproses...' : 'Konfirmasi Pembayaran'}
                    </button>
                </div>

            </div>

            {showCashModal && (
                <div style={{
                    position: 'fixed', inset: 0,
                    background: 'rgba(28,25,23,0.55)',
                    zIndex: 200,
                    display: 'flex', alignItems: 'center', justifyContent: 'center',
                    padding: '0 24px',
                }}>
                    <div style={{
                        background: C.surface,
                        borderRadius: 20,
                        width: '100%', maxWidth: 320,
                        padding: '28px 24px 24px',
                        display: 'flex', flexDirection: 'column',
                        alignItems: 'center', gap: 14,
                        boxShadow: '0 12px 40px rgba(28,25,23,0.20)',
                    }}>
                        <div style={{
                            width: 64, height: 64, borderRadius: 16,
                            background: C.bg, border: `1px solid ${C.border}`,
                            display: 'flex', alignItems: 'center', justifyContent: 'center',
                        }}>
                            <Wallet size={30} color={C.accent} strokeWidth={1.75} />
                        </div>

                        <div style={{
                            fontSize: 17, fontWeight: 700, color: C.textHead,
                            fontFamily: F, textAlign: 'center',
                        }}>
                            Bayar di Kasir
                        </div>

                        <div style={{
                            fontSize: 13, color: C.textSecond, lineHeight: 1.6,
                            fontFamily: F, textAlign: 'center',
                        }}>
                            Silakan tunjukkan pesanan ini ke kasir dan lakukan pembayaran tunai.
                        </div>

                        <div style={{
                            width: '100%',
                            background: C.bg, borderRadius: 12,
                            border: `1px solid ${C.border}`,
                            padding: '14px 16px',
                            display: 'flex', flexDirection: 'column',
                            alignItems: 'center', gap: 3,
                        }}>
                            <span style={{ fontSize: 10, color: C.textSecond, fontFamily: F, textTransform: 'uppercase', letterSpacing: '0.08em' }}>
                                No. Pesanan
                            </span>
                            <span style={{
                                fontSize: 15, fontWeight: 700, color: C.textHead,
                                fontFamily: F, letterSpacing: '0.02em', marginBottom: 8,
                            }}>
                                #{cashOrderCode}
                            </span>
                            <span style={{ fontSize: 10, color: C.textSecond, fontFamily: F, textTransform: 'uppercase', letterSpacing: '0.08em' }}>
                                Total Pembayaran
                            </span>
                            <span style={{
                                fontSize: 22, fontWeight: 700, color: C.textHead,
                                fontFamily: F, letterSpacing: '-0.02em',
                            }}>
                                {formatRupiah(order.total_amount)}
                            </span>
                        </div>

                        <div style={{
                            width: '100%', background: C.bg,
                            borderRadius: 10, border: `1px solid ${C.border}`,
                            padding: '10px 14px',
                        }}>
                            <span style={{ fontSize: 12, color: C.textSecond, lineHeight: 1.5, fontFamily: F }}>
                                Pantau status pesananmu di tab{' '}
                                <strong style={{ color: C.accent }}>Riwayat</strong>
                                {' '}untuk melihat update dari kasir.
                            </span>
                        </div>

                        <button
                            onClick={handleMengerti}
                            className="w9p-confirm"
                            style={{
                                width: '100%', padding: '13px 0',
                                background: C.accent, color: '#FFFFFF',
                                border: 'none', borderRadius: 12,
                                fontSize: 15, fontWeight: 700, cursor: 'pointer',
                                fontFamily: F,
                                boxShadow: '0 4px 16px rgba(68,64,60,0.25)',
                            }}
                        >
                            Mengerti
                        </button>
                    </div>
                </div>
            )}
        </PelangganLayout>
    );
}
