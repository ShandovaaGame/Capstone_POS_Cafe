import { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import PelangganLayout from '@/Layouts/PelangganLayout';
import { formatRupiah } from '@/helpers';

function useSnapScript(snapUrl) {
    const [ready, setReady] = useState(!!window.snap);

    useEffect(() => {
        if (window.snap) { setReady(true); return; }
        const script = document.createElement('script');
        script.src = snapUrl;
        script.setAttribute('data-client-key', '');
        script.onload = () => setReady(true);
        document.head.appendChild(script);
    }, [snapUrl]);

    return ready;
}

export default function PaymentProcess({ snapToken, order, clientKey, snapUrl }) {
    const snapReady = useSnapScript(snapUrl);
    const [error, setError]           = useState(null);
    const [showRetry, setShowRetry]   = useState(false);
    const [launched, setLaunched]     = useState(false);

    useEffect(() => {
        if (!snapReady || !snapToken || launched) return;
        setLaunched(true);

        window.snap.pay(snapToken, {
            onSuccess: () => {
                router.visit(`/customer/order/${order.order_code}/status`);
            },
            onPending: () => {
                router.visit(`/customer/order/${order.order_code}/status`);
            },
            onError: () => {
                setError('Pembayaran gagal. Silakan coba lagi.');
                setShowRetry(true);
            },
            onClose: () => {
                setShowRetry(true);
            },
        });
    }, [snapReady, snapToken, launched]);

    return (
        <PelangganLayout activeTab="riwayat">
            <div style={{
                display: 'flex', flexDirection: 'column',
                alignItems: 'center', justifyContent: 'center',
                minHeight: 'calc(100vh - 92px)',
                padding: '0 24px', gap: 24,
            }}>

                {/* Order info */}
                <div style={{
                    background: '#FFFFFF', borderRadius: 20,
                    border: '1px solid #EDE8E2', padding: 24,
                    width: '100%', textAlign: 'center',
                    boxShadow: '0 4px 14px rgba(45,32,22,0.06)',
                }}>
                    <span style={{ fontSize: 12, color: '#B5A898', fontFamily: 'Outfit, system-ui, sans-serif' }}>
                        Pesanan
                    </span>
                    <div style={{ fontSize: 17, fontWeight: 700, color: '#2D2016', margin: '4px 0 2px', fontFamily: 'Outfit, system-ui, sans-serif' }}>
                        #{order.order_code}
                    </div>
                    <div style={{ fontSize: 22, fontWeight: 700, color: '#E8763A', fontFamily: 'Outfit, system-ui, sans-serif' }}>
                        {formatRupiah(order.total_amount)}
                    </div>
                </div>

                {/* Error state */}
                {error && (
                    <div style={{
                        background: '#FEF2F2', border: '1px solid #FCA5A5',
                        borderRadius: 12, padding: '14px 16px', width: '100%',
                        color: '#DC3545', fontSize: 14,
                        fontFamily: 'Outfit, system-ui, sans-serif',
                    }}>
                        ⊗ {error}
                    </div>
                )}

                {/* Loading state */}
                {!showRetry && !error && (
                    <div style={{ textAlign: 'center' }}>
                        <div style={{
                            width: 44, height: 44, margin: '0 auto 16px',
                            border: '4px solid #EDE8E2',
                            borderTop: '4px solid #E8763A',
                            borderRadius: '50%',
                            animation: 'spin 0.8s linear infinite',
                        }} />
                        <span style={{ fontSize: 14, color: '#8C7B6B', fontFamily: 'Outfit, system-ui, sans-serif' }}>
                            Menghubungkan ke pembayaran...
                        </span>
                    </div>
                )}

                {/* Retry button */}
                {showRetry && (
                    <div style={{ display: 'flex', flexDirection: 'column', gap: 12, width: '100%' }}>
                        <button
                            onClick={() => {
                                setShowRetry(false);
                                setError(null);
                                setLaunched(false);
                            }}
                            style={{
                                background: '#E8763A', color: '#FFFFFF',
                                border: 'none', borderRadius: 50,
                                height: 52, width: '100%',
                                fontSize: 15, fontWeight: 600,
                                fontFamily: 'Outfit, system-ui, sans-serif',
                                cursor: 'pointer',
                            }}
                        >
                            Coba Lagi
                        </button>
                        <button
                            onClick={() => router.visit('/pelanggan/riwayat')}
                            style={{
                                background: 'transparent', color: '#8C7B6B',
                                border: '1px solid #EDE8E2', borderRadius: 50,
                                height: 48, width: '100%',
                                fontSize: 14, fontWeight: 500,
                                fontFamily: 'Outfit, system-ui, sans-serif',
                                cursor: 'pointer',
                            }}
                        >
                            Kembali ke Riwayat
                        </button>
                    </div>
                )}
            </div>

            <style>{`
                @keyframes spin { to { transform: rotate(360deg); } }
            `}</style>
        </PelangganLayout>
    );
}
