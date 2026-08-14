import { useState, useEffect } from 'react';
import { router, Head } from '@inertiajs/react';
import axios from 'axios';
import { Coffee } from 'lucide-react';
import PelangganLayout from '@/Layouts/PelangganLayout';
import useCart from '@/Hooks/useCart';
import { formatRupiah } from '@/helpers';

const F = '"Inter", system-ui, sans-serif';
const C = {
    bg:          '#F7F5F2',
    surface:     '#FFFFFF',
    alt:         '#EFEDE9',
    border:      '#E2DED8',
    accent:      '#44403C',
    textPrimary: '#1C1917',
    textSecond:  '#78716C',
    textMuted:   '#A8A29E',
    success:     '#059669',
};

export default function CustomerCart() {
    const { items, tableId, updateQty, total, count } = useCart();
    const [loading,     setLoading]     = useState(false);
    const [errorMsg,    setErrorMsg]    = useState('');
    const [isMahasiswa, setIsMahasiswa] = useState(false);

    const isEmpty = items.length === 0;

    useEffect(() => {
        try {
            const saved = sessionStorage.getItem('w9_customer');
            if (saved) setIsMahasiswa(JSON.parse(saved).isMahasiswa === true);
        } catch (_) {}
    }, []);

    const totalCashback = isMahasiswa
        ? items.reduce((s, i) => s + (i.cashback ?? 0) * i.quantity, 0)
        : 0;
    const grandTotal = total - totalCashback;

    const handleIncrement = (menuId) => {
        const item = items.find(i => i.menuId === menuId);
        if (item) updateQty(menuId, item.quantity + 1);
    };
    const handleDecrement = (menuId) => {
        const item = items.find(i => i.menuId === menuId);
        if (item) updateQty(menuId, item.quantity - 1);
    };

    async function handleCheckout() {
        if (isEmpty) return;
        setErrorMsg('');
        let customer = null;
        try { customer = JSON.parse(sessionStorage.getItem('w9_customer') || 'null'); } catch (_) {}
        if (!customer?.name || !customer?.phone) {
            router.visit(`/order?table=${tableId ?? ''}`);
            return;
        }
        setLoading(true);
        try {
            const res = await axios.post('/api/order', {
                customer_name:  customer.name,
                customer_phone: customer.phone,
                table_id:       customer.tableId,
                is_mahasiswa:   isMahasiswa,
                items: items.map(i => ({ menu_id: i.menuId, quantity: i.quantity })),
            });
            router.visit(`/pelanggan/bayar/${res.data.order_code}/pilih`);
        } catch (err) {
            const msg = err.response?.data?.message ?? err.response?.data?.errors ?? 'Terjadi kesalahan. Coba lagi.';
            setErrorMsg(typeof msg === 'object' ? Object.values(msg).flat().join(' ') : msg);
        } finally {
            setLoading(false);
        }
    }

    return (
        <PelangganLayout activeTab="cart">
            <Head>
                <title>Keranjang — W9 Cafe</title>
                <link rel="preconnect" href="https://fonts.googleapis.com" />
                <link rel="preconnect" href="https://fonts.gstatic.com" crossOrigin="anonymous" />
                <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" />
                <style>{`
                    html, body { background: ${C.bg}; }
                    .w9cart-btn:active { transform: scale(0.98); }
                    .w9qty-btn:active  { opacity: 0.75; }
                    ::-webkit-scrollbar { display: none; }
                `}</style>
            </Head>

            <div style={{
                position: 'fixed', top: 0, left: '50%', transform: 'translateX(-50%)',
                width: '100%', maxWidth: 430, height: '100vh',
                zIndex: 0, pointerEvents: 'none', overflow: 'hidden', background: C.bg,
            }}>
                <img src="/images/wallpaper-menu.jpg" alt=""
                    style={{ width: '100%', height: '100%', objectFit: 'cover', objectPosition: 'center top', display: 'block' }}
                />
            </div>

            <div style={{
                position: 'fixed', top: 0, left: '50%', transform: 'translateX(-50%)',
                width: '100%', maxWidth: 430, height: '100vh',
                display: 'flex', flexDirection: 'column', zIndex: 1,
            }}>

                <header style={{
                    paddingTop: 32, paddingBottom: 16,
                    paddingLeft: 24, paddingRight: 24,
                    textAlign: 'center', flexShrink: 0,
                }}>
                    <h1 style={{
                        fontSize: 17, fontWeight: 700, color: C.textPrimary,
                        fontFamily: F, letterSpacing: '0.10em', textTransform: 'uppercase',
                        margin: 0,
                    }}>
                        Keranjang
                    </h1>
                    <p style={{ fontSize: 13, color: C.textSecond, fontFamily: F, marginTop: 4, marginBottom: 0 }}>
                        {isEmpty ? 'Belum ada item' : `${count} item tersimpan`}
                    </p>
                </header>

                <div style={{
                    flex: 1, overflowY: 'auto', scrollbarWidth: 'none',
                    WebkitOverflowScrolling: 'touch',
                    padding: '0 24px', paddingBottom: 100,
                }}>

                    {isEmpty ? (
                        <div style={{
                            display: 'flex', flexDirection: 'column', alignItems: 'center',
                            justifyContent: 'center', gap: 16, padding: '60px 8px',
                            minHeight: 'calc(100vh - 180px)',
                        }}>
                            <div style={{
                                width: 72, height: 72, borderRadius: 18,
                                background: 'rgba(255,255,255,0.85)',
                                border: `1px solid ${C.border}`,
                                display: 'flex', alignItems: 'center', justifyContent: 'center',
                                backdropFilter: 'blur(6px)',
                            }}>
                                <Coffee size={30} color={C.textMuted} strokeWidth={1.5} />
                            </div>
                            <div style={{ textAlign: 'center' }}>
                                <p style={{ fontSize: 15, fontWeight: 600, color: C.textPrimary, margin: '0 0 6px', fontFamily: F }}>
                                    Keranjang Kosong
                                </p>
                                <p style={{ fontSize: 13, color: C.textMuted, margin: 0, fontFamily: F, lineHeight: 1.6 }}>
                                    Tambahkan menu favoritmu dari halaman menu
                                </p>
                            </div>
                            <button
                                onClick={() => router.visit(`/pelanggan/menu?table=${tableId ?? ''}`)}
                                className="w9cart-btn"
                                style={{
                                    marginTop: 4, height: 46, padding: '0 28px',
                                    background: C.accent, color: C.bg,
                                    border: 'none', borderRadius: 8,
                                    fontSize: 14, fontWeight: 600, cursor: 'pointer', fontFamily: F,
                                    transition: 'transform 0.1s',
                                }}>
                                Kembali ke Menu
                            </button>
                        </div>

                    ) : (
                        <div style={{ display: 'flex', flexDirection: 'column', gap: 12, paddingTop: 4 }}>

                            {items.map((item) => {
                                const cb       = isMahasiswa ? (item.cashback ?? 0) : 0;
                                const effPrice = item.price - cb;
                                const subtotal = effPrice * item.quantity;
                                return (
                                    <article key={item.menuId} style={{
                                        background: 'rgba(255,255,255,0.90)',
                                        backdropFilter: 'blur(8px)',
                                        WebkitBackdropFilter: 'blur(8px)',
                                        borderRadius: 12, padding: 16,
                                        border: `1px solid ${C.border}`,
                                        display: 'flex', alignItems: 'center', gap: 16,
                                        boxShadow: '0 1px 4px rgba(0,0,0,0.04)',
                                    }}>
                                        <div style={{
                                            width: 64, height: 64, borderRadius: 10,
                                            background: C.alt, flexShrink: 0,
                                            display: 'flex', alignItems: 'center', justifyContent: 'center',
                                            overflow: 'hidden',
                                        }}>
                                            {item.image
                                                ? <img src={item.image} alt={item.name} style={{ width: '100%', height: '100%', objectFit: 'cover' }} />
                                                : <Coffee size={24} color={C.textSecond} strokeWidth={1.5} />
                                            }
                                        </div>

                                        <div style={{ flex: 1, minWidth: 0 }}>
                                            <h3 style={{
                                                fontSize: 14, fontWeight: 600,
                                                color: C.textPrimary, fontFamily: F,
                                                margin: '0 0 2px',
                                            }}>
                                                {item.name}
                                            </h3>
                                            <p style={{ fontSize: 11, color: C.textSecond, fontFamily: F, margin: '0 0 4px' }}>
                                                {formatRupiah(effPrice)} × {item.quantity}
                                            </p>
                                            <p style={{ fontSize: 14, fontWeight: 700, color: C.textPrimary, fontFamily: F, margin: 0 }}>
                                                {formatRupiah(subtotal)}
                                            </p>
                                        </div>

                                        <div style={{
                                            display: 'flex', alignItems: 'center', gap: 10,
                                            background: C.alt, padding: '6px 8px', borderRadius: 8,
                                            flexShrink: 0,
                                        }}>
                                            <button
                                                onClick={() => handleDecrement(item.menuId)}
                                                className="w9qty-btn"
                                                style={{
                                                    width: 26, height: 26, borderRadius: 6,
                                                    background: C.surface, border: `1px solid ${C.border}`,
                                                    cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center',
                                                    fontSize: 15, color: C.textSecond, fontFamily: F, transition: 'opacity 0.1s',
                                                }}>−</button>
                                            <span style={{
                                                fontSize: 13, fontWeight: 600, color: C.textPrimary,
                                                minWidth: 18, textAlign: 'center', fontFamily: F,
                                            }}>
                                                {item.quantity}
                                            </span>
                                            <button
                                                onClick={() => handleIncrement(item.menuId)}
                                                className="w9qty-btn"
                                                style={{
                                                    width: 26, height: 26, borderRadius: 6,
                                                    background: C.accent, border: 'none',
                                                    cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center',
                                                    fontSize: 15, fontWeight: 600, color: '#FFFFFF', fontFamily: F, transition: 'opacity 0.1s',
                                                }}>+</button>
                                        </div>
                                    </article>
                                );
                            })}

                            <section style={{
                                background: 'rgba(255,255,255,0.90)',
                                backdropFilter: 'blur(8px)',
                                WebkitBackdropFilter: 'blur(8px)',
                                borderRadius: 12, padding: 20,
                                border: `1px solid ${C.border}`,
                                boxShadow: '0 1px 4px rgba(0,0,0,0.04)',
                                marginTop: 4,
                            }}>
                                <p style={{
                                    fontSize: 10, fontWeight: 600, color: C.textSecond,
                                    letterSpacing: '0.10em', textTransform: 'uppercase',
                                    fontFamily: F, margin: '0 0 16px',
                                }}>
                                    Ringkasan Pesanan
                                </p>

                                <div style={{ borderBottom: `1px solid ${C.border}`, paddingBottom: 14, marginBottom: 14, display: 'flex', flexDirection: 'column', gap: 8 }}>
                                    <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                                        <span style={{ fontSize: 13, color: C.textSecond, fontFamily: F }}>Subtotal</span>
                                        <span style={{ fontSize: 13, color: C.textPrimary, fontFamily: F }}>{formatRupiah(total)}</span>
                                    </div>
                                    {isMahasiswa && totalCashback > 0 && (
                                        <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                                            <span style={{ fontSize: 13, color: C.success, fontFamily: F }}>Cashback Mahasiswa</span>
                                            <span style={{ fontSize: 13, color: C.success, fontFamily: F }}>− {formatRupiah(totalCashback)}</span>
                                        </div>
                                    )}
                                    <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                                        <span style={{ fontSize: 13, color: C.textSecond, fontFamily: F }}>Biaya Layanan</span>
                                        <span style={{ fontSize: 13, color: C.textPrimary, fontFamily: F }}>Gratis</span>
                                    </div>
                                </div>

                                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                                    <span style={{ fontSize: 15, fontWeight: 700, color: C.textPrimary, fontFamily: F }}>Total</span>
                                    <span style={{ fontSize: 17, fontWeight: 700, color: C.textPrimary, fontFamily: F }}>
                                        {formatRupiah(grandTotal)}
                                    </span>
                                </div>
                            </section>

                            {errorMsg && (
                                <div style={{
                                    background: '#FEF2F2', border: '1px solid #FECACA',
                                    borderRadius: 10, padding: '10px 14px',
                                    fontSize: 13, color: '#DC2626', fontFamily: F,
                                }}>
                                    {errorMsg}
                                </div>
                            )}

                            <div style={{ paddingTop: 4 }}>
                                <button
                                    onClick={handleCheckout}
                                    disabled={loading}
                                    className="w9cart-btn"
                                    style={{
                                        width: '100%', height: 54,
                                        background: loading ? C.textMuted : C.accent,
                                        color: C.bg, border: 'none', borderRadius: 8,
                                        fontSize: 15, fontWeight: 700,
                                        cursor: loading ? 'not-allowed' : 'pointer',
                                        fontFamily: F,
                                        boxShadow: loading ? 'none' : '0 4px 12px rgba(68,64,60,0.25)',
                                        transition: 'transform 0.1s',
                                    }}
                                >
                                    {loading ? 'Memproses...' : `Lanjut Pembayaran  ${formatRupiah(grandTotal)}`}
                                </button>
                            </div>

                        </div>
                    )}
                </div>
            </div>
        </PelangganLayout>
    );
}
